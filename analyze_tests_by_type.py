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
        type_ = failure.get('type', '')
        
        msg = failure.get('message', '')
        if not msg and failure.text:
            msg = failure.text
        msg = msg.strip().split('\n')[0]
        
        # for Exception types, sometimes it's in the first line of the message
        if ':' in msg and 'Exception' in msg.split(':')[0]:
             type_ = msg.split(':')[0].strip()
             
        if 'Return value must be of type bool, null returned' in msg:
             type_ = 'TypeError: Return value must be of type bool, null returned'
        
        if 'Expected response status code' in msg:
            type_ = msg
            
        classname = testcase.get('classname', '')
        name = testcase.get('name', '')
        errors_by_type[type_].append(f"{classname}::{name}")

for t, tests in errors_by_type.items():
    print(f"TYPE: {t} (Count: {len(tests)})")
    for test in tests[:3]:
        print(f"  - {test}")
    print("-" * 40)
