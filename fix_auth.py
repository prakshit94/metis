import re

filepath = 'app/Modules/Users/Controllers/AuthController.php'
with open(filepath, 'r') as f:
    content = f.read()

pattern = r"(\$response = redirect\(\)->intended\(route\('dashboard'\)\);)"
replacement = r"""$intendedUrl = $request->session()->get('url.intended');
        if ($intendedUrl && (str_contains($intendedUrl, '/api/') || str_contains($intendedUrl, '/broadcasting/'))) {
            $request->session()->forget('url.intended');
        }
        
        \1"""

content = re.sub(pattern, replacement, content)

with open(filepath, 'w') as f:
    f.write(content)

print("Auth fix applied")
