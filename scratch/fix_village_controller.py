import re

with open('/home/user/metis/app/Modules/Core/Controllers/VillageController.php', 'r') as f:
    content = f.read()

pattern = r"new Middleware\('permission:village-view', only: \['index', 'show', 'servicesOptions', 'search'\]\),"
replacement = r"new Middleware('permission:village-view', only: ['index', 'show', 'servicesOptions']),"

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/app/Modules/Core/Controllers/VillageController.php', 'w') as f:
    f.write(content)
