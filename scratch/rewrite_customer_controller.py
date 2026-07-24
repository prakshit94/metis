import re

with open('/home/user/metis/app/Modules/Customers/Controllers/CustomerController.php', 'r') as f:
    content = f.read()

auth_logic = """
        $user = $request->user();
        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));

        $customers = Customer::query()
            ->when(!$isGlobalView, fn ($q) => $q->where('created_by', $user->id))"""

content = content.replace("        $customers = Customer::query()", auth_logic)

with open('/home/user/metis/app/Modules/Customers/Controllers/CustomerController.php', 'w') as f:
    f.write(content)
