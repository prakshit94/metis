import sys

file_path = "/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php"

with open(file_path, "r") as f:
    lines = f.readlines()

new_permissions = [
    "'invoices.view',",
    "'invoices.create',",
    "'invoices.edit',",
    "'invoices.delete',",
    "'payments.view',",
    "'payments.create',",
    "'payments.edit',",
    "'payments.delete',",
    "'refunds.view',",
    "'refunds.create',",
    "'refunds.edit',",
    "'refunds.delete',",
    "'returns.view',",
    "'returns.create',",
    "'returns.edit',",
    "'returns.delete',"
]

new_lines = []
inserted_all_perms = False
inserted_manager_perms = False

for i, line in enumerate(lines):
    new_lines.append(line)
    
    # Insert in PERMISSIONS array
    if not inserted_all_perms and "orders.view" in line and "orders.view." not in line:
        indent = line[:len(line) - len(line.lstrip())]
        for p in new_permissions:
            new_lines.append(f"{indent}{p}\n")
        inserted_all_perms = True

    # Insert in Manager permissions array
    if not inserted_manager_perms and "'Manager'     => [" in line:
        # Search for orders.view in manager section
        pass

for i, line in enumerate(new_lines):
    if "'Manager'     => [" in line:
        # found manager section start
        # find orders.view in this block
        for j in range(i, len(new_lines)):
            if "orders.view" in new_lines[j] and "orders.view." not in new_lines[j]:
                indent = new_lines[j][:len(new_lines[j]) - len(new_lines[j].lstrip())]
                # Insert view permissions
                view_perms = [p for p in new_permissions if "view" in p]
                for p in reversed(view_perms):
                    new_lines.insert(j+1, f"{indent}{p}\n")
                inserted_manager_perms = True
                break
        break

with open(file_path, "w") as f:
    f.writelines(new_lines)

print("Permissions added to seeder.")
