<?php
/**
 * House Rental Management System
 * Main Index / Homepage
 */

// Include initialization file
require_once 'includes/init.php';

// Page title
$page_title = 'Home';

// Get featured houses
$house = new House();
$featured_houses = $house->getAvailableHouses();

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-slider">
        <div class="hero-slide" style="background-image: url('<?php echo IMG_PATH; ?>/hero-1.jpg');">
            <div class="container">
                <div class="row">
                    <div class="col-md-8 offset-md-2 text-center">
                        <div class="hero-content">
                            <h1 class="display-4 text-white mb-4">Find Your Perfect Rental Home</h1>
                            <p class="lead text-white mb-5">Browse our wide selection of properties and find the perfect place to call home.</p>
                            <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-primary btn-lg">View Available Houses</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="search-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="card search-card">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Find Your Dream Rental</h3>
                        <form action="<?php echo BASE_URL; ?>/houses.php" method="get">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="category">Property Type</label>
                                        <select name="category" id="category" class="form-control">
                                            <option value="">All Types</option>
                                            <?php 
                                            $db->query("SELECT * FROM categories ORDER BY name");
                                            $categories = $db->resultSet();
                                            foreach($categories as $category): 
                                            ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="price_max">Maximum Price</label>
                                        <select name="price_max" id="price_max" class="form-control">
                                            <option value="">Any Price</option>
                                            <option value="10000">Under ₹10,000</option>
                                            <option value="20000">Under ₹20,000</option>
                                            <option value="30000">Under ₹30,000</option>
                                            <option value="50000">Under ₹50,000</option>
                                            <option value="100000">Under ₹100,000</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="submit">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">Search Properties</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="featured-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2>Featured Properties</h2>
            <p class="text-muted">Browse our selection of available rental properties</p>
        </div>
        
        <div class="row">
            <?php if(empty($featured_houses)): ?>
            <div class="col-12 text-center">
                <div class="alert alert-info">
                    <h4>No houses available at the moment.</h4>
                    <p>Please check back later for new listings.</p>
                </div>
            </div>
            <?php else: ?>
                <?php 
                // Show at most 6 properties
                $counter = 0;
                foreach($featured_houses as $house): 
                    if($counter >= 6) break;
                    $counter++;
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card house-card">
                        <div class="house-status available">Available</div>
                        <img src="<?php echo BASE_URL . '/' . ($house['image_path'] ? $house['image_path'] : 'assets/img/house-default.jpg'); ?>" class="card-img-top" alt="<?php echo $house['house_no']; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $house['house_no']; ?></h5>
                            <p class="card-text">
                                <i class="fas fa-tag mr-2"></i><?php echo $house['category']; ?>
                            </p>
                            <p class="card-text house-desc"><?php echo substr($house['description'], 0, 100); ?>...</p>
                            <div class="house-price mb-3">₹<?php echo number_format($house['price'], 2); ?></div>
                            <a href="<?php echo BASE_URL; ?>/house_details.php?id=<?php echo $house['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-outline-primary btn-lg">View All Properties</a>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2>How It Works</h2>
            <p class="text-muted">Renting a house has never been easier</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="icon-box mb-4">
                            <i class="fas fa-search fa-3x text-primary"></i>
                        </div>
                        <h4 class="card-title">Search Properties</h4>
                        <p class="card-text">Browse our extensive list of available rental properties to find your perfect home.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="icon-box mb-4">
                            <i class="fas fa-home fa-3x text-primary"></i>
                        </div>
                        <h4 class="card-title">Book a House</h4>
                        <p class="card-text">Submit a booking request for the property you're interested in.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="icon-box mb-4">
                            <i class="fas fa-key fa-3x text-primary"></i>
                        </div>
                        <h4 class="card-title">Move In</h4>
                        <p class="card-text">Once your booking is approved, complete the payment and move into your new home.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2>What Our Tenants Say</h2>
            <p class="text-muted">Read testimonials from satisfied tenants</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <div class="testimonial-rating mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"The booking process was incredibly smooth, and the house is even better than the pictures. Highly recommended!"</p>
                        <div class="testimonial-author mt-4">
                            <div class="author-info">
                                <h5 class="author-name mb-0">Rahul Sharma</h5>
                                <small class="text-muted">Tenant since 2023</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <div class="testimonial-rating mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        </div>
                        <p class="card-text">"Great selection of properties, and the online payment system makes paying rent so convenient. The staff is also very responsive."</p>
                        <div class="testimonial-author mt-4">
                            <div class="author-info">
                                <h5 class="author-name mb-0">Priya Patel</h5>
                                <small class="text-muted">Tenant since 2022</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <div class="testimonial-rating mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"I was able to find my dream apartment in just a few days. The maintenance team is quick to respond to any issues. Excellent service!"</p>
                        <div class="testimonial-author mt-4">
                            <div class="author-info">
                                <h5 class="author-name mb-0">Arjun Singh</h5>
                                <small class="text-muted">Tenant since 2023</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2 class="mb-3">Ready to Find Your New Home?</h2>
                <p class="lead mb-0">Join our platform today and discover the perfect rental property for you.</p>
            </div>
            <div class="col-lg-4 text-lg-right">
                <a href="<?php echo BASE_URL; ?>/tenant/register.php" class="btn btn-light btn-lg">Sign Up Now</a>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS for this page -->
<style>
    /* Hero Section */
    .hero-section {
        position: relative;
        height: 600px;
        overflow: hidden;
    }
    
    .hero-slide {
        height: 600px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }
    
    .hero-slide::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
    }
    
    .hero-content {
        position: relative;
        padding: 150px 0;
    }
    
    /* Search Card */
    .search-card {
        margin-top: -70px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        border: none;
        border-radius: 15px;
        z-index: 10;
        position: relative;
    }
    
    /* Icon Box */
    .icon-box {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        background-color: rgba(78, 115, 223, 0.1);
    }
    
    /* Testimonial Card */
    .testimonial-card {
        border: none;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }
    
    .testimonial-card:hover {
        transform: translateY(-10px);
    }
    
    .testimonial-author {
        display: flex;
        align-items: center;
    }
    
    /* CTA Section */
    .cta-section {
        background-color: var(--primary-color);
    }
</style>

<?php include 'includes/footer.php'; ?> 