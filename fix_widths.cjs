const fs = require('fs');
const path = require('path');

const dir = 'resources/js/Pages/Reportes';
const files = fs.readdirSync(dir).filter(f => f.endsWith('.vue'));

files.forEach(file => {
    const filePath = path.join(dir, file);
    let content = fs.readFileSync(filePath, 'utf8');
    
    // Replace class="w-40" with style="width: 160px;"
    content = content.replace(/class="w-40"/g, 'style="width: 160px;"');
    
    // Replace class="w-48" with style="width: 192px;"
    content = content.replace(/class="w-48"/g, 'style="width: 192px;"');
    
    fs.writeFileSync(filePath, content, 'utf8');
});

console.log('Replaced successfully.');
