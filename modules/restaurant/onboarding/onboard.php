<?php
/**
 * Zesto — Restaurant Onboarding Wizard
 * Step-by-step onboarding flow for new restaurant owners.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/upload_helper.php';

requireRole(ROLE_RESTAURANT_OWNER);
$currentUser = getCurrentUser();
$ownerId = $currentUser['id'];

// Check if they already have a restaurant
$res = db()->prepare("SELECT id FROM restaurants WHERE owner_id = :oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
if ($res->fetch()) {
    header('Location: ' . BASE_URL . '/restaurant-panel/dashboard.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? 'Mumbai');
    $tags = trim($_POST['tags'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $hours = trim($_POST['operating_hours'] ?? '9:00 AM - 10:00 PM');
    $radius = floatval($_POST['delivery_radius'] ?? 5.0);

    if (empty($name)) { $errors[] = 'Restaurant name is required.'; }
    if (empty($phone)) { $errors[] = 'Contact phone number is required.'; }
    if (empty($address)) { $errors[] = 'Restaurant address is required.'; }

    // Generate unique slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    $chk = db()->prepare("SELECT id FROM restaurants WHERE slug = :s LIMIT 1");
    $chk->execute([':s' => $slug]);
    if ($chk->fetch()) {
        $slug = $slug . '-' . time();
    }

    // Handle Uploads
    $logoUrl = 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=800&q=80'; // fallback
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadLogo = handleImageUpload($_FILES['logo'], 'logos', $errors);
        if ($uploadLogo) {
            $logoUrl = $uploadLogo;
        }
    }

    $bannerUrl = 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=800&q=80'; // fallback
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadBanner = handleImageUpload($_FILES['banner'], 'banners', $errors);
        if ($uploadBanner) {
            $bannerUrl = $uploadBanner;
        }
    }

    if (empty($errors)) {
        try {
            $stmt = db()->prepare("INSERT INTO restaurants 
                (owner_id, slug, name, tags, description, phone, address, city, operating_hours, delivery_radius, logo_image, banner_image, image, rating, rating_count, delivery_time, delivery_time_value, distance, delivery_fee, is_free_delivery) 
                VALUES 
                (:oid, :slug, :name, :tags, :desc, :phone, :address, :city, :hours, :radius, :logo, :banner, :image, 4.0, 1, '20-30 min', 25, 2.0, 30.00, 0)");
            
            $stmt->execute([
                ':oid' => $ownerId,
                ':slug' => $slug,
                ':name' => $name,
                ':tags' => $tags,
                ':desc' => $description,
                ':phone' => $phone,
                ':address' => $address,
                ':city' => $city,
                ':hours' => $hours,
                ':radius' => $radius,
                ':logo' => $logoUrl,
                ':banner' => $bannerUrl,
                ':image' => $logoUrl
            ]);

            setFlash('success', 'Congratulations! Your kitchen is now onboarded on Zesto!');
            header('Location: ' . BASE_URL . '/restaurant-panel/dashboard.php');
            exit;
        } catch (PDOException $ex) {
            $errors[] = 'Failed to create restaurant profile: ' . $ex->getMessage();
        }
    }
}

$pageTitle = 'Restaurant Onboarding — Zesto';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="min-h-screen bg-white/5 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans">
  <div class="max-w-2xl w-full space-y-8 glass-panel p-10 rounded-3xl border border-white/10 shadow-md shadow-black/20 relative overflow-hidden">
    
    <!-- Top Brand Accent -->
    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-orange-500 to-red-600"></div>

    <div class="text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-50 text-orange-600 font-black text-2xl mb-4">
        🍴
      </div>
      <h2 class="text-3xl font-black text-white">Setup Your Kitchen Profile</h2>
      <p class="text-sm text-white/60 mt-2">Get started and reach thousands of hungry customers nearby.</p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-xs text-red-600 font-semibold space-y-1">
      <?php foreach ($errors as $e): ?><p>• <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Onboarding Step Indicator -->
    <div class="flex items-center justify-between mb-10 text-[10px] font-bold text-white/40 uppercase tracking-wider select-none">
      <span class="step-indicator active text-orange-600 border-b-2 border-orange-500 pb-2 flex-1 text-center" data-step="1">1. Profile</span>
      <span class="step-indicator flex-1 border-b-2 border-white/10 pb-2 text-center" data-step="2">2. Contact</span>
      <span class="step-indicator flex-1 border-b-2 border-white/10 pb-2 text-center" data-step="3">3. Logistics</span>
      <span class="step-indicator flex-1 border-b-2 border-white/10 pb-2 text-center" data-step="4">4. Branding</span>
    </div>

    <!-- Onboarding Form -->
    <form method="POST" enctype="multipart/form-data" id="onboard-form" class="space-y-6 text-xs text-white/70 font-semibold">
      <?= csrfField() ?>

      <!-- STEP 1: KITCHEN PROFILE -->
      <div class="step-panel" id="step-panel-1">
        <h3 class="text-base font-black text-white mb-4">Tell us about your Kitchen</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Restaurant Kitchen Name *</label>
            <input type="text" name="name" required placeholder="e.g. Royal Punjab Dhaba" class="zesto-input bg-gray-50/50 text-xs">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Cuisines / Specialties *</label>
            <input type="text" name="tags" required placeholder="e.g. North Indian, Curry, Biryani" class="zesto-input bg-gray-50/50 text-xs">
            <span class="text-[10px] text-white/40 font-medium mt-1 block">Comma-separated tags for display and filtering.</span>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Short Description</label>
            <textarea name="description" rows="3" placeholder="Describe your kitchen's special recipe, standard of hygiene, or legacy..." class="zesto-input bg-gray-50/50 text-xs resize-none"></textarea>
          </div>
        </div>
      </div>

      <!-- STEP 2: CONTACT DETAILS -->
      <div class="step-panel hidden" id="step-panel-2">
        <h3 class="text-base font-black text-white mb-4">Contact & Location</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Contact Phone Number *</label>
            <input type="text" name="phone" required placeholder="e.g. +91 98765 43210" class="zesto-input bg-gray-50/50 text-xs">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">City Location *</label>
            <select name="city" required class="zesto-input bg-gray-50/50 text-xs text-white/80">
              <option value="Mumbai">Mumbai</option>
              <option value="Delhi">Delhi</option>
              <option value="Bangalore">Bangalore</option>
              <option value="Pune">Pune</option>
              <option value="Hyderabad">Hyderabad</option>
              <option value="Chennai">Chennai</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Full Kitchen Address *</label>
            <textarea name="address" required rows="3" placeholder="e.g. Shop 4, Link Heights, Carter Road, Bandra West" class="zesto-input bg-gray-50/50 text-xs resize-none"></textarea>
          </div>
        </div>
      </div>

      <!-- STEP 3: LOGISTICS & HOURS -->
      <div class="step-panel hidden" id="step-panel-3">
        <h3 class="text-base font-black text-white mb-4">Operating Parameters</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Operating Hours *</label>
            <input type="text" name="operating_hours" required value="9:00 AM - 10:00 PM" placeholder="e.g. 10:00 AM - 11:00 PM" class="zesto-input bg-gray-50/50 text-xs">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Delivery Radius (in km) *</label>
            <input type="number" name="delivery_radius" required step="0.1" value="5.0" min="0.5" max="30.0" class="zesto-input bg-gray-50/50 text-xs">
            <span class="text-[10px] text-white/40 font-medium mt-1 block">Maximum distance you want to serve.</span>
          </div>
        </div>
      </div>

      <!-- STEP 4: BRANDING -->
      <div class="step-panel hidden" id="step-panel-4">
        <h3 class="text-base font-black text-white mb-4">Kitchen Branding</h3>
        <div class="space-y-5">
          <div class="p-4 bg-orange-50/50 rounded-2xl border border-orange-100 flex items-start gap-3">
            <span class="text-lg">📸</span>
            <p class="text-[10px] leading-relaxed text-orange-800 font-semibold">
              High-quality photos increase order rates by up to 80%! Upload square logo and landscape banner for the best appearance. If skipped, premium placeholder images will be used.
            </p>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Upload Restaurant Logo (Square)</label>
            <input type="file" name="logo" accept="image/*" class="zesto-input bg-gray-50/50 text-xs py-2 px-3 border-dashed border-2">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Upload Restaurant Banner (Landscape)</label>
            <input type="file" name="banner" accept="image/*" class="zesto-input bg-gray-50/50 text-xs py-2 px-3 border-dashed border-2">
          </div>
        </div>
      </div>

      <!-- Button Controls -->
      <div class="flex justify-between items-center pt-6 border-t border-white/10">
        <button type="button" id="prev-btn" class="hidden font-bold border border-white/10 text-white/60 rounded-xl h-11 px-5 hover:bg-white/5 transition-colors">
          Back
        </button>
        <div class="flex-1"></div>
        <button type="button" id="next-btn" class="btn-primary w-fit font-bold rounded-xl h-11 px-6">
          Next Step
        </button>
        <button type="submit" id="submit-btn" class="hidden btn-primary w-fit font-bold rounded-xl h-11 px-6 bg-gradient-to-r from-orange-500 to-red-600">
          Complete Registration & Launch 🚀
        </button>
      </div>
    </form>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 4;

    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const submitBtn = document.getElementById('submit-btn');
    const onboardForm = document.getElementById('onboard-form');

    const updateWizard = () => {
        // Show/hide panels
        document.querySelectorAll('.step-panel').forEach(panel => panel.classList.add('hidden'));
        document.getElementById(`step-panel-${currentStep}`).classList.remove('hidden');

        // Update indicators
        document.querySelectorAll('.step-indicator').forEach(ind => {
            const step = parseInt(ind.dataset.step);
            ind.classList.remove('text-orange-600', 'border-orange-500');
            if (step === currentStep) {
                ind.classList.add('text-orange-600', 'border-orange-500');
            }
        });

        // Update button visibilities
        if (currentStep === 1) {
            prevBtn.classList.add('hidden');
        } else {
            prevBtn.classList.remove('hidden');
        }

        if (currentStep === totalSteps) {
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
        }
    };

    nextBtn.addEventListener('click', () => {
        // Validate inputs in the current active panel
        const currentPanel = document.getElementById(`step-panel-${currentStep}`);
        const inputs = currentPanel.querySelectorAll('input[required], select[required], textarea[required]');
        
        let valid = true;
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.reportValidity();
                valid = false;
            }
        });

        if (valid && currentStep < totalSteps) {
            currentStep++;
            updateWizard();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    updateWizard();
});
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
