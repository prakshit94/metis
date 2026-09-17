import os
import glob

controllers_path = 'app/Modules/Orders/Controllers/*.php'
for filepath in glob.glob(controllers_path):
    with open(filepath, 'r') as f:
        content = f.read()
    
    print(f"\n--- {filepath} ---")
    
    # check for public function show
    if "public function show" in content:
        print("Has show()")
    if "public function update" in content:
        print("Has update()")
    if "public function destroy" in content:
        print("Has destroy()")
    if "public function index" in content:
        print("Has index()")
        
