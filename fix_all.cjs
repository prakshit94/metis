const fs = require('fs');
const path = require('path');

const configs = [
    { file: 'orders.js', arr: 'orders', sel: 'selectedOrders', mapField: 'o => String(o.id)' },
    { file: 'products.js', arr: 'products', sel: 'selectedProducts', mapField: 'p => String(p.id)' },
    { file: 'customers.js', arr: 'customers', sel: 'selectedCustomers', mapField: 'c => String(c.id)' },
    { file: 'invoices.js', arr: 'invoices', sel: 'selectedInvoices', mapField: 'i => String(i.id)' },
    { file: 'payments.js', arr: 'payments', sel: 'selectedPayments', mapField: 'p => String(p.id)' },
    { file: 'refunds.js', arr: 'refunds', sel: 'selectedRefunds', mapField: 'r => String(r.id)' },
    { file: 'files.js', arr: 'files', sel: 'selectedFiles', mapField: 'f => f.id' }
];

for (const c of configs) {
    const p = path.join('./resources/js/components', c.file);
    if (!fs.existsSync(p)) continue;
    
    let content = fs.readFileSync(p, 'utf8');
    
    // Fix toggleAll
    // Orders: this.selectedOrders = this.orders.map(o => String(o.id));
    // Products: this.selectedProducts = this.products.map(p => String(p.id));
    // Customers: this.selectedCustomers = this.customers.map(c => String(c.id));
    // Invoices: this.selectedInvoices = this.invoices.map(i => String(i.id));
    // Payments: this.selectedPayments = this.payments.map(p => String(p.id));
    // Refunds: this.selectedRefunds = this.refunds.map(r => String(r.id));
    
    const toggleAllRegex = new RegExp(`toggleAll\\s*\\(checked\\)\\s*\\{\\s*if\\s*\\(checked\\)\\s*\\{\\s*this\\.${c.sel}\\s*=\\s*this\\.${c.arr}\\.map\\([^)]+\\);\\s*\\}\\s*else\\s*\\{\\s*this\\.${c.sel}\\s*=\\s*\\[\\];\\s*\\}\\s*\\}`, 'g');
    
    content = content.replace(toggleAllRegex, `toggleAll(checked) {
      if (checked) {
        this.${c.arr}.forEach(item => {
          if (!this.${c.sel}.includes(String(item.id))) {
            this.${c.sel}.push(String(item.id));
          }
        });
      } else {
        const currentIds = this.${c.arr}.map(item => String(item.id));
        this.${c.sel} = this.${c.sel}.filter(id => !currentIds.includes(id));
      }
    }`);

    // Fix clear on filter
    const filterNames = ['filterOrders', 'filterProducts', 'filterCustomers', 'filterInvoices', 'filterPayments', 'filterRefunds', 'filterFiles'];
    
    for (const f of filterNames) {
        const fRegex = new RegExp(`${f}\\s*\\(\\)\\s*\\{[^}]*?this\\.${c.sel}\\s*=\\s*\\[\\];[^}]*?\\}`, 'g');
        content = content.replace(fRegex, (match) => {
            return match.replace(new RegExp(`this\\.${c.sel}\\s*=\\s*\\[\\];\\s*\\n?`), '');
        });
    }

    fs.writeFileSync(p, content, 'utf8');
    console.log('Fixed', p);
}
