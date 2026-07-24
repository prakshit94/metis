import re

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'r') as f:
    content = f.read()

content = content.replace(
    "$filter = $request->input('filter', 'all');", 
    "$filter = $request->input('filter', 'today');"
)

# Optional: remove the this_year condition just to clean it up, though not strictly necessary if UI doesn't send it.
# Actually we can just leave the this_year condition, it doesn't hurt.

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'w') as f:
    f.write(content)
