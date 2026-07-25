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
            const content = fs.readFileSync(fullPath, 'utf8');
            const lines = content.split('\n');
            let inFilter = false;
            for (let i = 0; i < lines.length; i++) {
                if (lines[i].match(/filter[A-Za-z0-9_]*\(\)/) || lines[i].match(/sortBy\(/)) {
                    inFilter = true;
                }
                if (lines[i].match(/^\s*\}\s*$/) || lines[i].match(/^\s*\},?\s*$/)) {
                    // end of some block, might be end of filter
                }
                
                if (lines[i].match(/this\.selected[a-zA-Z0-9_]*\s*=\s*\[\]/)) {
                    console.log(fullPath, ':', i+1, lines[i].trim());
                }
            }
        }
    }
}
processDir(dir);
