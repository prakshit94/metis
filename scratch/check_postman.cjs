const fs = require('fs');

const postmanFile = fs.readFileSync('postman/Metis-API-Collection.json', 'utf8');
const postman = JSON.parse(postmanFile);

const routesFile = fs.readFileSync('storage/routes.json', 'utf8');
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
        
        // clean up path
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

console.log(`Total API routes from Laravel: ${apiRoutes.length}`);
console.log(`Total requests in Postman: ${postmanPaths.length}`);

// Find routes in Laravel not in Postman
const missingInPostman = [];
for (const r of apiRoutes) {
  let matched = false;
  const methods = r.method.split('|');
  for (const method of methods) {
    if (method === 'HEAD' || method === 'OPTIONS') continue;
    
    // Convert laravel {param} to postman :param or just match base path
    const normalizedLaravelUri = r.uri.replace(/{[^}]+}/g, (match) => {
      return ':' + match.substring(1, match.length - 1);
    });

    const found = postmanPaths.find(p => {
      // Very loose match for demonstration
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

console.log(`\nRoutes missing in Postman (${missingInPostman.length}):`);
for (const m of missingInPostman) {
  console.log(`- [${m.method}] ${m.uri} (${m.action})`);
}

// Write a simple report
fs.writeFileSync('storage/postman_diff.txt', missingInPostman.map(m => `[${m.method}] ${m.uri} (${m.action})`).join('\n'));
