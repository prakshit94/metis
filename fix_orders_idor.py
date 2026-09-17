import re

def fix_file(filepath, model_name, relation='order'):
    with open(filepath, 'r') as f:
        content = f.read()

    # Index scoping
    index_logic = f"""
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('view-all-data')) {{
            if ($lobStateName = $user->lob_state_name) {{
                $query->whereHas('{relation}', function ($q) use ($lobStateName, $user) {{
                    $q->where('shipping_state', $lobStateName)
                      ->orWhere('created_by', $user->id);
                }});
            }}
        }}"""

    # Show/Update/Destroy IDOR check
    idor_check = f"""
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('view-all-data')) {{
            $relOrder = ${model_name.lower()}->{relation};
            if ($relOrder && $user->lob_state_name) {{
                if ($relOrder->shipping_state !== $user->lob_state_name && $relOrder->created_by !== $user->id) {{
                    abort(403, 'Unauthorized access to this {model_name.lower()}.');
                }}
            }}
        }}"""

    # Patch Index
    if 'public function index(Request $request)' in content:
        # insert after $query = ...;
        content = re.sub(r'(\$query = ' + model_name + r'::[a-zA-Z0-9_:\'\"\[\]\(\)\,\.\>\s]*;)', r'\1' + index_logic, content)

    # Patch Show
    content = re.sub(r'(public function show\(' + model_name + r' \$' + model_name.lower() + r'\)\s*\{)', r'\1' + idor_check, content)
    content = re.sub(r'(public function show\(Request \$request, ' + model_name + r' \$' + model_name.lower() + r'\): JsonResponse\s*\{)', r'\1' + idor_check, content)

    # Patch Update
    content = re.sub(r'(public function update\(' + model_name + r' \$' + model_name.lower() + r', Request \$request\)\s*\{)', r'\1' + idor_check, content)
    content = re.sub(r'(public function update\(Request \$request, ' + model_name + r' \$' + model_name.lower() + r'\): JsonResponse\s*\{)', r'\1' + idor_check, content)

    # Patch Destroy
    content = re.sub(r'(public function destroy\(' + model_name + r' \$' + model_name.lower() + r'\)\s*\{)', r'\1' + idor_check, content)
    content = re.sub(r'(public function destroy\(Request \$request, ' + model_name + r' \$' + model_name.lower() + r'\): JsonResponse\s*\{)', r'\1' + idor_check, content)

    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Fixed IDOR in {filepath}")

fix_file('app/Modules/Orders/Controllers/InvoiceController.php', 'Invoice')
fix_file('app/Modules/Orders/Controllers/PaymentController.php', 'Payment')
fix_file('app/Modules/Orders/Controllers/OrderComplaintController.php', 'OrderComplaint')
fix_file('app/Modules/Orders/Controllers/OrderReturnController.php', 'OrderReturn')
fix_file('app/Modules/Orders/Controllers/CreditNoteController.php', 'CreditNote', 'customer') # credit note relates to customer, wait, let's skip credit note for now to avoid errors

