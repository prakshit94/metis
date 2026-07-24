import re

with open('/home/user/metis/app/Modules/Orders/Controllers/OrderController.php', 'r') as f:
    content = f.read()

pattern = r"(    private function applyOrderActionPermissionScope\(\$query, \$user\): void\n    \{\n        if \(!\$user\) \{\n            \$query->whereRaw\('1 = 0'\);\n            return;\n        \}\n\n        if \(\$user->hasAnyRole\(\['Super Admin', 'Admin'\]\) \|\| \(\$user->can\('view_all_order'\) \|\| \$user->can\('view-all-data'\)\)\) \{\n            return;\n        \}\n\n)(        \$query->where\(function \(\$q\) use \(\$user\) \{.*?\n        \}\);)(.*?    \})"
# We want to replace the whole closure with just $query->where('created_by', $user->id);

def replacer(match):
    return match.group(1) + "        $query->where('created_by', $user->id);" + match.group(3)

# Since the closure is huge, regex with .*? might fail or be slow if not re.DOTALL
new_content = re.sub(
    r"(    private function applyOrderActionPermissionScope\(\$query, \$user\): void\n    \{\n        if \(!\$user\) \{\n            \$query->whereRaw\('1 = 0'\);\n            return;\n        \}\n\n        if \(\$user->hasAnyRole\(\['Super Admin', 'Admin'\]\) \|\| \(\$user->can\('view_all_order'\) \|\| \$user->can\('view-all-data'\)\)\) \{\n            return;\n        \}\n\n)        \$query->where\(function \(\$q\) use \(\$user\) \{.*?\n        \}\);",
    r"\1        $query->where('created_by', $user->id);",
    content,
    flags=re.DOTALL
)

with open('/home/user/metis/app/Modules/Orders/Controllers/OrderController.php', 'w') as f:
    f.write(new_content)
