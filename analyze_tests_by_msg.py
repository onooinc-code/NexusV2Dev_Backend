import xml.etree.ElementTree as ET
from collections import defaultdict
import sys

try:
    tree = ET.parse('junit.xml')
    root = tree.getroot()
except Exception as e:
    print(f"Error reading junit.xml: {e}")
    sys.exit(1)

errors_by_type = defaultdict(list)
for testcase in root.iter('testcase'):
    for failure in testcase.findall('failure') + testcase.findall('error'):
        msg = failure.get('message', '')
        if not msg and failure.text:
            msg = failure.text
        msg = msg.strip().split('\n')[0]
        
        type_ = failure.get('type', '')
        if ':' in msg and 'Exception' in msg.split(':')[0]:
             type_ = msg.split(':')[0].strip()
             
        if 'Expected response status code' in msg:
            key = msg
        else:
            key = f"{type_}: {msg}"
            
        classname = testcase.get('classname', '')
        name = testcase.get('name', '')
        errors_by_type[key].append(f"{classname}::{name}")

for t, tests in errors_by_type.items():
    print(f"ERROR: {t} (Count: {len(tests)})")
    for test in tests[:3]:
        print(f"  - {test}")
    print("-" * 40)
