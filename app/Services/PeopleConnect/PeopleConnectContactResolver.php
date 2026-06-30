<?php

namespace App\Services\PeopleConnect;

use App\Models\Contact;
use App\Models\ContactIdentifier;
use App\Services\ContactIdentityResolver;
use App\Services\ContactHubService;
use Illuminate\Support\Str;

class PeopleConnectContactResolver
{
    public function __construct(
        protected ContactIdentityResolver $identityResolver,
        protected ContactHubService $contactHubService
    ) {}

    /**
     * Resolves a Contact based on WAHA provided details.
     * Creates a new Contact if not found.
     *
     * @param string $chatId WAHA chatId
     * @param string $phone Extracted phone number from WAHA
     * @param string $displayName WAHA pushname/displayName
     * @return Contact
     */
    public function resolve(string $chatId, string $phone, string $displayName = ''): Contact
    {
        // ISSUE RESOLVED: Resolved Contact Identity Resolution Race Condition.
        // We use Cache::lock() (Redis atomic locks) based on the phone number to serialize resolution.
        // Concurrent requests block and wait up to 5 seconds. Once the lock is acquired, we perform
        // the check again before attempting creation to prevent duplicate contacts.
        $lock = \Illuminate\Support\Facades\Cache::lock("contact_resolve_{$phone}", 10);

        try {
            $lock->block(5);

            // 1. Try to resolve using ContactIdentityResolver
            $identifiers = [
                ['type' => 'whatsapp', 'value' => $phone],
                ['type' => ContactIdentifier::TYPE_PHONE, 'value' => $phone],
            ];

            $contact = $this->identityResolver->resolve($identifiers);

            if ($contact) {
                // Ensure the whatsapp identifier is linked if it wasn't
                $this->identityResolver->linkIdentifier($contact, 'whatsapp', $phone, false);
                return $contact;
            }

            // 2. Not found, create new Contact
            $contactName = !empty($displayName) ? $displayName : 'WAHA Contact ' . substr($phone, -4);

            $contact = Contact::create([
                'name' => $contactName,
                'phone' => $phone,
                'whatsapp_number' => $phone,
                'type' => 'lead',
                'is_active' => true,
            ]);

            // Link the identifiers
            $this->identityResolver->linkIdentifier($contact, ContactIdentifier::TYPE_PHONE, $phone, true);
            $this->identityResolver->linkIdentifier($contact, 'whatsapp', $phone, false);

            // Run sync contact details via Hub
            $this->contactHubService->syncContactDetails($contact);

            return $contact;
        } finally {
            $lock->release();
        }
    }
}
