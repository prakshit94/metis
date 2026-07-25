const fs = require('fs');
const path = require('path');

const dir = './resources/js/components';

function processDir(directory) {
    const files = fs.readdirSync(directory);
    for (const file of files) {
        const fullPath = path.join(directory, file);
        if (fs.statSync(fullPath).isDirectory()) {
            processDir(fullPath);
        } else if (fullPath.endsWith('.js')) {
            let content = fs.readFileSync(fullPath, 'utf8');
            let modified = false;

            // Remove selected.* = [] from filter methods
            // Find "filter" methods like filterData(), filterOrders(), filterReturns(), sortBy() etc.
            
            // Just use a simpler regex that replaces the specific line inside filter functions
            // By finding "filterX() {" and finding the first "this.selectedX = [];" before a "}"
            
            // Actually, we know the method names are like filterData, filterReturns, filterOrders, filterCustomers, etc.
            const filterMethodNames = ['filterData', 'filterReturns', 'filterOrders', 'filterCustomers', 'filterInvoices', 'filterPayments', 'filterRefunds', 'filterProducts', 'filterFiles', 'sortBy', 'goToPage'];
            
            for (const methodName of filterMethodNames) {
                // regex to match the method block approximately
                const methodRegex = new RegExp(`${methodName}\\s*\\([a-zA-Z0-9_,\\s]*\\)\\s*\\{([\\s\\S]*?)^\\s*\\}`, 'gm');
                
                content = content.replace(methodRegex, (match, body) => {
                    if (body.match(/this\.selected[a-zA-Z0-9_]*\s*=\s*\[\];/)) {
                        modified = true;
                        const newBody = body.replace(/this\.selected[a-zA-Z0-9_]*\s*=\s*\[\];\n?/, '');
                        return match.replace(body, newBody);
                    }
                    return match;
                });
            }

            if (modified) {
                fs.writeFileSync(fullPath, content, 'utf8');
                console.log('Fixed filter', fullPath);
            }
        }
    }
}

processDir(dir);
