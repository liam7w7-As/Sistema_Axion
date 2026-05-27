const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  // Need to login first because the routes are protected
  await page.goto('http://localhost:8000/login');
  await page.type('input[type="email"]', 'admin@admin.com'); // Assuming default admin login or similar, wait I don't know the login.
  
  // Let's just output the computed styles of an el-select in a blank HTML page with Element Plus and Tailwind
  await browser.close();
})();
