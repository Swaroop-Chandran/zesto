<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

$error = '';
$success = '';

// Fetch current settings
$stmt = db()->query("SELECT * FROM delivery_settings WHERE id = 1 LIMIT 1");
$settings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $baseFare   = (float)($_POST['base_fare'] ?? 40.00);
    $perKm      = (float)($_POST['per_km_charge'] ?? 5.00);
    $minCharge  = (float)($_POST['min_delivery_charge'] ?? 40.00);
    $peakHour   = (float)($_POST['peak_hour_bonus'] ?? 0.00);
    $rain       = (float)($_POST['rain_bonus'] ?? 0.00);
    $festival   = (float)($_POST['festival_bonus'] ?? 0.00);

    try {
        $upd = db()->prepare("
            UPDATE delivery_settings 
            SET base_fare = :bf, 
                per_km_charge = :pk, 
                min_delivery_charge = :mc, 
                peak_hour_bonus = :ph, 
                rain_bonus = :rb, 
                festival_bonus = :fb
            WHERE id = 1
        ");
        $upd->execute([
            ':bf' => $baseFare,
            ':pk' => $perKm,
            ':mc' => $minCharge,
            ':ph' => $peakHour,
            ':rb' => $rain,
            ':fb' => $festival
        ]);
        
        setFlash('success', 'Delivery settings updated successfully! 🎉');
        header('Location: ' . BASE_URL . '/admin/delivery_settings.php');
        exit;
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Fetch updated settings
$stmt = db()->query("SELECT * FROM delivery_settings WHERE id = 1 LIMIT 1");
$settings = $stmt->fetch();

$pageTitle = 'Delivery Settings — Admin Zesto';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'admin'; $activePage = 'delivery_settings.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-zesto-orange uppercase tracking-widest">Admin Control Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Delivery Settings</h1>
        <p class="text-xs text-white/60 mt-1">Configure global delivery fare algorithms and active incentive bonuses</p>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 font-semibold">
      ❌ <?= e($error) ?>
    </div>
    <?php endif; ?>

    <div class="glass-panel rounded-3xl border border-white/10 p-6 md:p-8 shadow-md shadow-black/20">
      <form method="POST" class="flex flex-col gap-6 text-xs text-white/70 font-semibold">
        <?= csrfField() ?>

        <!-- Fare Calculation Config -->
        <div class="space-y-4">
          <h3 class="font-extrabold text-sm text-zesto-orange uppercase tracking-wider border-b border-white/10 pb-2">🏍 Core Delivery Fare</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-white/70 mb-1.5 uppercase">Base Fare (₹) *</label>
              <input type="number" step="0.01" name="base_fare" required value="<?= e($settings['base_fare']) ?>" class="zesto-input bg-gray-50/50">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/70 mb-1.5 uppercase">Per KM Charge (₹) *</label>
              <input type="number" step="0.01" name="per_km_charge" required value="<?= e($settings['per_km_charge']) ?>" class="zesto-input bg-gray-50/50">
            </div>
          </div>
          
          <div>
            <label class="block text-[10px] font-bold text-white/70 mb-1.5 uppercase">Minimum Delivery Charge (₹) *</label>
            <input type="number" step="0.01" name="min_delivery_charge" required value="<?= e($settings['min_delivery_charge']) ?>" class="zesto-input bg-gray-50/50">
            <span class="text-[9px] text-white/40 font-medium block mt-1">Ensures partners always make a baseline amount per delivery task</span>
          </div>
        </div>

        <!-- Incentive Bonuses config -->
        <div class="space-y-4">
          <h3 class="font-extrabold text-sm text-zesto-orange uppercase tracking-wider border-b border-white/10 pb-2">🌧 Active Delivery Incentives</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-white/70 mb-1.5 uppercase">Peak Hour Bonus (₹)</label>
              <input type="number" step="0.01" name="peak_hour_bonus" required value="<?= e($settings['peak_hour_bonus']) ?>" class="zesto-input bg-gray-50/50">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/70 mb-1.5 uppercase">Rain Bonus (₹)</label>
              <input type="number" step="0.01" name="rain_bonus" required value="<?= e($settings['rain_bonus']) ?>" class="zesto-input bg-gray-50/50">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-white/70 mb-1.5 uppercase">Festival Bonus (₹)</label>
              <input type="number" step="0.01" name="festival_bonus" required value="<?= e($settings['festival_bonus']) ?>" class="zesto-input bg-gray-50/50">
            </div>
          </div>
        </div>

        <button type="submit" class="w-full btn-primary bg-[#a83300] hover:bg-[#c93c02] text-white h-12 justify-center font-bold tracking-wide mt-4 rounded-xl text-xs cursor-pointer shadow-md shadow-black/20">
          Save Settings &amp; Apply Globally 🎉
        </button>
      </form>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
