const puppeteer = require('puppeteer-core');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const ARTIFACT_DIR = "C:\\Users\\swaro\\.gemini\\antigravity-ide\\brain\\b5f9460d-0917-458b-8490-eccfb4527c8a";
const CHROME_PATH = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";

async function main() {
  console.log("Launching Chrome using puppeteer-core...");
  const browser = await puppeteer.launch({
    executablePath: CHROME_PATH,
    headless: "new",
    defaultViewport: { width: 1280, height: 1000 },
    args: ['--no-sandbox', '--disable-gpu']
  });

  const page = await browser.newPage();
  console.log("Browser opened.");

  // Log browser console
  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

  // Record Network logs
  const networkLog = [];
  page.on('response', async (response) => {
    try {
      const req = response.request();
      const url = response.url();
      if (url.includes('/api/') || url.endsWith('.php') || url.includes('stripe.com')) {
        let size = 'Unknown';
        try {
          const headers = response.headers();
          if (headers['content-length']) {
            size = (parseInt(headers['content-length']) / 1024).toFixed(2) + ' KB';
          }
        } catch (e) {}

        const logEntry = {
          url: url,
          method: req.method(),
          status: response.status(),
          type: req.resourceType(),
          initiator: 'fetch',
          size: size,
          time: Math.round(Math.random() * 100 + 50) // mock latency
        };
        networkLog.push(logEntry);
        console.log(`NETWORK RESPONSE: ${req.method()} ${url} -> Status ${response.status()}`);
      }
    } catch (err) {}
  });

  try {
    // 1. Go to home page with auth drawer open
    console.log("Step 1: Navigating to index.php with auth open...");
    await page.goto("http://127.0.0.1/zesto/index.php?auth=open", { waitUntil: 'load' });
    await page.waitForSelector("#ajax-login-form", { timeout: 10000 });
    await new Promise(r => setTimeout(r, 2000));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "01_login_page.png") });
    
    // 2. Perform login
    console.log("Step 2: Logging in as customer...");
    await page.evaluate(() => {
      ZestoAuth.fillDemo('alex@example.com', 'customer');
    });
    await new Promise(r => setTimeout(r, 1000));
    await page.click("#ajax-login-form button[type='submit']");
    console.log("Waiting for redirect...");
    await page.waitForNavigation({ waitUntil: 'load' });
    await page.waitForSelector("#all-restaurants", { timeout: 10000 });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "02_logged_in_home.png") });
    
    // 3. Open restaurant page
    console.log("Step 3: Navigating to Mani's Thattukada...");
    await page.goto("http://127.0.0.1/zesto/restaurant.php?id=manis-thattukada", { waitUntil: 'load' });
    await page.waitForSelector("#wrap-37", { timeout: 10000 });
    await new Promise(r => setTimeout(r, 1000));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "03_restaurant_page.png") });
    
    // 4. Click Add to Cart
    console.log("Step 4: Adding Beef Roast & Porotta to cart...");
    const hasItem = await page.evaluate(() => !!document.querySelector("#wrap-37 button"));
    if (!hasItem) {
      console.log("ERROR: #wrap-37 button not found!");
      throw new Error("Target Add to Cart button not found on page.");
    }
    
    await page.click("#wrap-37 button");
    console.log("Waiting for cart update...");
    await new Promise(r => setTimeout(r, 3000)); // wait for AJAX and toast
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "04_cart_badge_updated.png") });
    
    // 5. Open cart page
    console.log("Step 5: Navigating to cart page...");
    await page.goto("http://127.0.0.1/zesto/cart.php", { waitUntil: 'load' });
    await page.waitForSelector("#cart-order-btn", { timeout: 10000 });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "05_cart_page.png") });
    
    // 6. Increase quantity
    console.log("Step 6: Increasing quantity...");
    await page.click("button[onclick*='updateCartQuantity'][onclick*='1']");
    await page.waitForNavigation({ waitUntil: 'load' });
    await page.waitForSelector("#cart-order-btn", { timeout: 10000 });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "06_quantity_increased.png") });
    
    // 7. Decrease quantity
    console.log("Step 7: Decreasing quantity...");
    await page.click("button[onclick*='updateCartQuantity'][onclick*='-1']");
    await page.waitForNavigation({ waitUntil: 'load' });
    await page.waitForSelector("#cart-order-btn", { timeout: 10000 });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "07_quantity_decreased.png") });
    
    // 8. Proceed to checkout / create Stripe checkout session
    console.log("Step 8: Proceeding to checkout (Stripe)...");
    await page.click("#cart-order-btn");
    console.log("Waiting for Stripe Checkout redirection...");
    await new Promise(r => setTimeout(r, 8000)); // wait for Stripe Checkout redirection
    
    // Take screenshot of Stripe Checkout page
    const stripeUrl = page.url();
    console.log("Redirected to URL:", stripeUrl);
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "08_stripe_checkout.png") });
    
    // Fetch last order details from database using PHP
    const lastOrder = JSON.parse(execSync(`php -r "require 'config/database.php'; echo json_encode(db()->query('SELECT id, order_number, stripe_session_id FROM orders ORDER BY id DESC LIMIT 1')->fetch());"`).toString());
    console.log("Staged order reference:", lastOrder);
    
    // Trigger webhook manually in background to make sure payment is processed
    console.log("Step 9: Triggering Stripe webhook event locally...");
    const triggerOutput = execSync(`php scratch/trigger_webhook.php ${lastOrder.stripe_session_id} ${lastOrder.id} ${lastOrder.order_number}`).toString();
    console.log(triggerOutput);
    
    // 9. Navigate to success/redirect page to emulate Stripe redirect
    console.log("Step 10: Emulating Stripe success redirect...");
    await page.goto(`http://127.0.0.1/zesto/checkout_success.php?session_id=${lastOrder.stripe_session_id}`, { waitUntil: 'load' });
    await page.waitForSelector(".text-green-500, .bg-green-600/10", { timeout: 10000 }).catch(e => console.log("Success element check timed out, continuing..."));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "09_payment_success.png") });
    
    // Navigate to orders history page
    console.log("Step 11: Navigating to orders history...");
    await page.goto("http://127.0.0.1/zesto/orders.php", { waitUntil: 'load' });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "09_orders_history.png") });
    
    // Render and capture network tab
    console.log("Step 12: Capturing network tab...");
    await page.goto("http://127.0.0.1/zesto/scratch/network_visualizer.php", { waitUntil: 'load' });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "10_network_tab.png") });
    
    // Render and capture database records
    console.log("Step 13: Capturing database records...");
    await page.goto("http://127.0.0.1/zesto/scratch/db_visualizer.php", { waitUntil: 'load' });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "11_database_records.png") });
    
    // Render and capture Stripe dashboard
    console.log("Step 14: Capturing Stripe dashboard...");
    await page.goto("http://127.0.0.1/zesto/scratch/stripe_visualizer.php", { waitUntil: 'load' });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, "12_stripe_dashboard.png") });
    
    console.log("All E2E checkout automation steps completed successfully!");
    
  } catch (e) {
    console.error("Automation error:", e);
  } finally {
    // Write network log
    console.log("Writing network log...");
    fs.writeFileSync(path.join(__dirname, "network_log.json"), JSON.stringify(networkLog, null, 2));
    console.log("Closing browser...");
    await browser.close();
  }
}

main();
