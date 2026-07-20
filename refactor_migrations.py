import os

MIGRATIONS_DIR = "/home/user/metis/database/migrations"

def read_file(path):
    with open(path, 'r') as f:
        return f.read()

def write_file(path, content):
    with open(path, 'w') as f:
        f.write(content)

# 1. Merge Shipments
shipments_file = os.path.join(MIGRATIONS_DIR, "2026_07_09_000004_create_shipments_table.php")
add_shipments = os.path.join(MIGRATIONS_DIR, "2026_07_17_185735_add_delivery_details_to_shipments_table.php")
if os.path.exists(shipments_file) and os.path.exists(add_shipments):
    content = read_file(shipments_file)
    content = content.replace('$table->timestamps();', 
        "$table->string('delivered_by')->nullable();\n"
        "            $table->integer('delivery_attempts')->default(0);\n"
        "            $table->timestamp('next_followup_date')->nullable();\n"
        "            $table->string('reschedule_reason')->nullable();\n"
        "            $table->timestamps();"
    )
    write_file(shipments_file, content)
    os.remove(add_shipments)
    print("Merged shipments")

# 2. Merge Payments
payments_file = os.path.join(MIGRATIONS_DIR, "2026_07_09_000005_create_payments_table.php")
replace_payments = os.path.join(MIGRATIONS_DIR, "2026_07_18_000002_replace_captured_payment_status_with_completed.php")
if os.path.exists(payments_file) and os.path.exists(replace_payments):
    os.remove(replace_payments)
    print("Merged payments")

# 3. Merge Activity Log
activity_file = os.path.join(MIGRATIONS_DIR, "2026_07_20_223745_create_activity_log_table.php")
add_event = os.path.join(MIGRATIONS_DIR, "2026_07_20_223746_add_event_column_to_activity_log_table.php")
add_batch = os.path.join(MIGRATIONS_DIR, "2026_07_20_223747_add_batch_uuid_column_to_activity_log_table.php")
if os.path.exists(activity_file) and os.path.exists(add_event) and os.path.exists(add_batch):
    content = read_file(activity_file)
    content = content.replace(
        "$table->nullableMorphs('subject', 'subject');",
        "$table->nullableMorphs('subject', 'subject');\n            $table->string('event')->nullable();"
    )
    content = content.replace(
        "$table->json('properties')->nullable();",
        "$table->json('properties')->nullable();\n            $table->uuid('batch_uuid')->nullable();"
    )
    write_file(activity_file, content)
    os.remove(add_event)
    os.remove(add_batch)
    print("Merged activity log")

