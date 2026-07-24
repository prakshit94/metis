import re

with open('/home/user/metis/app/Modules/Customers/Controllers/CustomerController.php', 'r') as f:
    content = f.read()

auth_logic = """
        $user = $request->user();
        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));

        $query = Customer::where('phone', $phone);
        if (!$isGlobalView) {
            $query->where('created_by', $user->id);
        }
        
        $customer = $query->first();"""

content = content.replace("        $customer = Customer::where('phone', $phone)->first();", auth_logic)

with open('/home/user/metis/app/Modules/Customers/Controllers/CustomerController.php', 'w') as f:
    f.write(content)
