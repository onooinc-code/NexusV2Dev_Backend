import xml.etree.ElementTree as ET
from collections import defaultdict
import sys

try:
    tree = ET.parse('junit.xml')
    root = tree.getroot()
except Exception as e:
    print(f"Error reading junit.xml: {e}")
    sys.exit(1)

failures_by_message = defaultdict(list)

for testcase in root.iter('testcase'):
    for failure in testcase.findall('failure') + testcase.findall('error'):
        msg = failure.get('message', '')
        if not msg and failure.text:
            msg = failure.text
        
        msg = msg.strip()
        first_line = msg.split('\n')[0]
        
        classname = testcase.get('classname', '')
        name = testcase.get('name', '')
        
        failures_by_message[first_line].append(f"{classname}::{name}")

for msg, tests in failures_by_message.items():
    print("=" * 80)
    print(f"MESSAGE: {msg}")
    print(f"COUNT: {len(tests)}")
    print("EXAMPLES:")
    for t in tests[:5]:
        print(f"  - {t}")
    if len(tests) > 5:
        print(f"  - ... and {len(tests) - 5} more")
    print()
