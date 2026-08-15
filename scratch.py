import re

def simplify_blade():
    with open('resources/views/users/index.blade.php', 'r') as f:
        content = f.read()
    
    # We will try to extract the main structure of index.blade.php to see how large it is
    print(f"Length of index.blade.php: {len(content)}")
    
if __name__ == '__main__':
    simplify_blade()
