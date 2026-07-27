const fs = require('fs');
const path = require('path');

const postmanPath = path.resolve('postman/Metis-API-Collection.json');
const routesPath = path.resolve('storage/routes.json');

const postmanFile = fs.readFileSync(postmanPath, 'utf8');
const postman = JSON.parse(postmanFile);

const routesFile = fs.readFileSync(routesPath, 'utf8');
const routes = JSON.parse(routesFile);

const apiRoutes = routes.filter(r => r.uri.startsWith('api/'));

const extractPaths = (items, paths = []) => {
  for (const item of items) {
    if (item.item) {
      extractPaths(item.item, paths);
    } else if (item.request) {
      const url = item.request.url;
      if (url) {
        let pathStr = '';
        if (typeof url === 'string') {
          pathStr = url;
        } else if (url.raw) {
          pathStr = url.raw;
        }
        
        pathStr = pathStr.replace(/{{[a-zA-Z0-9_-]+}}/, '');
        pathStr = pathStr.replace(/^{{base_url}}\//, '');
        pathStr = pathStr.replace(/^https?:\/\/[^\/]+\//, '');
        if (pathStr.startsWith('/')) pathStr = pathStr.substring(1);
        
        paths.push({ name: item.name, path: pathStr, method: item.request.method });
      }
    }
  }
  return paths;
};

const postmanPaths = extractPaths(postman.item);

const missingInPostman = [];
for (const r of apiRoutes) {
  let matched = false;
  const methods = r.method.split('|');
  for (const method of methods) {
    if (method === 'HEAD' || method === 'OPTIONS') continue;
    
    const normalizedLaravelUri = r.uri.replace(/{[^}]+}/g, (match) => {
      return ':' + match.substring(1, match.length - 1);
    });

    const found = postmanPaths.find(p => {
      return p.method === method && 
             (p.path === r.uri || p.path === normalizedLaravelUri || p.path.split('?')[0] === r.uri);
    });

    if (found) {
      matched = true;
      break;
    }
  }
  
  if (!matched && !r.uri.includes('telescope') && !r.uri.includes('sanctum')) {
    missingInPostman.push(r);
  }
}

// Map logical groups
const getFolder = (uri) => {
    if (uri.includes('api/order-reasons')) return 'Order Reasons';
    if (uri.includes('api/permissions')) return 'Permissions';
    if (uri.includes('api/roles')) return 'Roles';
    if (uri.includes('api/users')) return 'Users';
    if (uri.includes('api/promotions')) return 'Promotions';
    if (uri.includes('api/inventory')) return 'Inventory';
    if (uri.includes('api/shipping')) return 'Shipping';
    if (uri.includes('api/products')) return 'Products';
    return 'Misc API';
};

const newFolders = {};
for (const m of missingInPostman) {
    const methods = m.method.split('|').filter(method => method !== 'HEAD' && method !== 'OPTIONS');
    if (methods.length === 0) continue;
    
    const method = methods[0];
    const folderName = getFolder(m.uri);
    
    if (!newFolders[folderName]) newFolders[folderName] = [];
    
    const urlParts = m.uri.split('/');
    const postmanUrlParts = urlParts.map(part => {
        if (part.startsWith('{') && part.endsWith('}')) {
            return `:${part.substring(1, part.length - 1)}`;
        }
        return part;
    });

    const item = {
        name: `${method} ${m.uri}`,
        request: {
            method: method,
            header: [
                {
                    key: "Accept",
                    value: "application/json",
                    type: "text"
                }
            ],
            url: {
                raw: `{{base_url}}/${m.uri.replace(/{[^}]+}/g, (match) => ':' + match.substring(1, match.length - 1))}`,
                host: [
                    "{{base_url}}"
                ],
                path: postmanUrlParts
            }
        },
        response: []
    };
    
    newFolders[folderName].push(item);
}

for (const [folderName, items] of Object.entries(newFolders)) {
    let folder = postman.item.find(i => i.name === folderName);
    if (!folder) {
        folder = {
            name: folderName,
            item: []
        };
        postman.item.push(folder);
    }
    
    for (const item of items) {
        folder.item.push(item);
    }
}

fs.writeFileSync(postmanPath, JSON.stringify(postman, null, 4));
console.log('Successfully updated Postman collection with missing routes.');
