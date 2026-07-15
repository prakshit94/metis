const fs = require('fs');
const content = fs.readFileSync('resources/views/orders/create.blade.php', 'utf8');
const match = content.match(/<script>([\s\S]*?)<\/script>/);
if (match) {
    try {
        // AlpineJS component is a function returning an object
        // we can wrap it and check syntax
        eval(match[1]);
        console.log("Syntax is OK");
    } catch (e) {
        console.error("Syntax Error:", e);
    }
} else {
    console.log("No script tag found");
}
