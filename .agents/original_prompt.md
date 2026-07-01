## 2026-07-01T04:10:31Z

Generate comprehensive, well-organized Markdown documentation for the NexusV3 project (which uses Laravel/Blade instead of Next.js). The agent team will deeply analyze the codebase to produce architecture maps, hub-specific docs, integration docs, and project readmes with Mermaid diagrams.

Important Context: The project is currently undergoing a major upgrade from Laravel 11 to Laravel 13 (see PROJECT.md). Ensure the documentation reflects Laravel 13 dependencies and structure where applicable.

Working directory: c:\Users\hedra\Desktop\NexusV3\Project\Nexus
Integrity mode: development

## Requirements

### R1. Deep Codebase Analysis
The agent team must read and analyze the entire NexusV3 codebase (controllers, Blade views, models, integrations, etc.) to understand the business logic, data flow, and architecture line-by-line.

### R2. Documentation Structure
Create or update the `Documentation` folder at the root of the project. Within it, ensure the following subdirectories exist and are fully populated:
- `Main-Files`
- `The-Hubs`
- `Integrations`
- `readme`
- `prompts`

### R3. Main Documentation Files
Generate deeply detailed Markdown files in `Documentation/Main-Files` including: DESIGN_PLAN_AND_ROADMAP, PROJECT_VISION, SYSTEM_ARCHITECTURE, THIRD_PARTY_INTEGRATIONS, ARCHITECTURE_STANDARDS, HUB_ARCHITECTURE, HUBS_UI_LAYOUT, RESPONSIVE_DESIGN, ANIMATION_GUIDELINES, DATA_FLOW, DEVELOPER_QUICK_START, TESTING_STANDARDS, DOCUMENTATION_STANDARDS, ERROR_HANDLING, PROJECT_OVERVIEW, COMPREHENSIVE_FEATURES_LIST, ARCHITECTURE_SPECIFICATION, API_DESIGN, DESIGN_SYSTEM, MOBILE_UI_SPECIFICS, NEXUS_AGENT_SYSTEM_INSTRUCTIONS, Data Models, Data Seeds, Bugs & Missing Features.

### R4. Hubs and Integrations Documentation
Identify all "Hubs" and "Integrations" in the codebase (e.g. AIModelsHub, ContactHub, WorkflowHub, Mem0Integration, etc).
For each Hub, create a dedicated folder inside `Documentation/The-Hubs/` containing highly detailed files: Requirements, Vision, Architecture, UI, Data Flow, Design System, Roadmap, and Bugs. These must reflect the actual code implementation.
For Integrations, document lists, credentials (mocked/placeholders if sensitive), and info inside `Documentation/Integrations/`.

### R5. Formatting and Content Standards
All documentation must be in clear, organized Markdown. Include Mermaid diagrams where applicable (e.g., Data Flow, System Architecture). Provide full details and explanations in every file based on the actual code, overriding the generic placeholders currently there.

## Acceptance Criteria

### Documentation Generation
- [ ] `Documentation` folder exists with all specified subfolders.
- [ ] All specified Main-Files exist and contain substantial, code-accurate Markdown content (not just stubs or generic placeholders).
- [ ] Hubs are dynamically identified from the code and documented with the required sub-files containing deep technical details.
- [ ] Integrations are identified and documented accurately.
- [ ] Mermaid diagrams are present and render correctly without syntax errors in at least the Architecture and Data Flow files.
