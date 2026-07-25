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

            // Find all toggleAll methods to identify the selected array and the items array
            const toggleAllRegex = /toggleAll\s*\(\s*checked\s*\)\s*\{\s*this\.([a-zA-Z0-9_]+)\s*=\s*checked\s*\?\s*this\.([a-zA-Z0-9_]+)\.map[^:]+:\s*\[\];\s*\}/g;
            
            content = content.replace(toggleAllRegex, (match, selectedArr, itemsArr) => {
                modified = true;
                return `toggleAll(checked) {
      if (checked) {
        this.${itemsArr}.forEach(item => {
          if (!this.${selectedArr}.includes(String(item.id))) {
            this.${selectedArr}.push(String(item.id));
          }
        });
      } else {
        const currentIds = this.${itemsArr}.map(item => String(item.id));
        this.${selectedArr} = this.${selectedArr}.filter(id => !currentIds.includes(id));
      }
    }`;
            });

            // If we found and replaced toggleAll, let's also fix the filter methods
            // We look for filter[A-Z]\w+\s*\(\)\s*\{[^}]+this\.([a-zA-Z0-9_]+)\s*=\s*\[\];
            if (modified) {
                const filterRegex = /filter[A-Za-z0-9_]+\s*\(\)\s*\{([^}]*?)this\.selected[a-zA-Z0-9_]+\s*=\s*\[\];([^}]*?)\}/g;
                content = content.replace(filterRegex, (match, before, after) => {
                    return match.replace(/this\.selected[a-zA-Z0-9_]+\s*=\s*\[\];/, '');
                });
                
                // also check filter() { ... }
                const plainFilterRegex = /filter\s*\(\)\s*\{([^}]*?)this\.selected[a-zA-Z0-9_]+\s*=\s*\[\];([^}]*?)\}/g;
                content = content.replace(plainFilterRegex, (match, before, after) => {
                    return match.replace(/this\.selected[a-zA-Z0-9_]+\s*=\s*\[\];/, '');
                });
                
                // check for toggleItem or toggleReturn or toggleOrder to ensure they toggle correctly?
                // actually Alpine usually uses x-model="selectedItems" so there is no toggleItem method.
                // Wait, if it uses x-model, it automatically adds/removes from the array. That's fine!

                fs.writeFileSync(fullPath, content, 'utf8');
                console.log('Modified', fullPath);
            }
        }
    }
}

processDir(dir);
