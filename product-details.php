<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('head.php');
    ?>
    <!--css link -->
    <link rel="stylesheet" href="./assets/css/product-details.css">
</head>

<body>
    <?php
    include('navbar.php');
    ?>

    <main class="product-details-page">
        <section class="breadcrumb">
            <span>Shop</span>
            <span>/</span>
            <span>Cameras</span>
            <span>/</span>
            <span>Mirrorless</span>
        </section>

        <section class="product-card">
            <div class="product-gallery">
                <div class="zoom-container" id="zoom-container">
                    <img
                        src="../storage/uploads/products/placeholder-camera.png"
                        alt="Product Image"
                        class="main-image"
                        id="main-image">
                </div>
                <div class="thumbnail-row" id="thumbnail-row">
                    <button class="thumb active" data-src="../storage/uploads/products/placeholder-camera.png">
                        <img src="../storage/uploads/products/placeholder-camera.png" alt="Thumbnail 1">
                    </button>
                    <button class="thumb" data-src="../storage/uploads/products/placeholder-camera.png">
                        <img src="../storage/uploads/products/placeholder-camera.png" alt="Thumbnail 2">
                    </button>
                    <button class="thumb" data-src="../storage/uploads/products/placeholder-camera.png">
                        <img src="../storage/uploads/products/placeholder-camera.png" alt="Thumbnail 3">
                    </button>
                    <button class="thumb" data-src="../storage/uploads/products/placeholder-camera.png">
                        <img src="../storage/uploads/products/placeholder-camera.png" alt="Thumbnail 4">
                    </button>
                    <button class="thumb" data-src="../storage/uploads/products/placeholder-camera.png">
                        <img src="../storage/uploads/products/placeholder-camera.png" alt="Thumbnail 5">
                    </button>
                </div>
            </div>
            <div class="product-info">
                <div class="title-row">
                    <h2>Canon EOS R6 Mark II</h2>
                    <div class="stock-badge">
                        <i class="fa-solid fa-fire"></i>
                        <span>Hurry! Only 2 Left!</span>
                    </div>
                </div>
                <div class="info-line">
                    <span class="label">Brand:</span> Canon
                </div>
                <div class="info-line">
                    <span class="label">Stock:</span>
                    <span class="in-stock">
                        <div class="in-stock-circle"></div>
                        In Stock
                    </span></div>
                <div class="info-line qty-line">
                    <span class="label">Quantity:</span>
                    <div class="qty-controls">
                        <button type="button">-</button>
                        <span>1</span>
                        <button type="button">+</button>
                    </div>
                </div>
                <div class="rating-row">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-regular fa-star"></i>
                </div>
                <div class="price-row">
                    <span class="old-price">3,500,000 MMK</span>
                    <span class="new-price">3,500,000 MMK</span>
                </div>
                <div class="save-pill">Save 1,000,000 MMK</div>
                <button class="primary-btn">Add to Cart</button>
            </div>
        </section>

        <section class="product-details-text">
            <h3>Description</h3>
            <p>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
            </p>
            <h4>Specifications</h4>
            <ul class="specifications">
                <li>Specification 1</li>
                <li>Specification 2</li>
                <li>Specification 3</li>
                <li>Specification 4</li>
                <li>Specification 5</li>
                <li>Specification 6</li>
            </ul>
        </section>

    <!--Unboxing & Influencers Video Link -->
    <section class="video-link-container">
        <div class="video-link-box">
            <div class="video-link-logo">
                <i class="fa-solid fa-photo-film"></i>
            </div>
            <div class="video-link-text">
                <h4>Unboxing & Influencers Videos</h4>
                <p>Experience our products through influencer reviews & unboxings.</p>
            </div>
            <div class="video-link-btn">
                <a href="./unboxing-influencers.php">Discover More</a>
            </div>
        </div>
    </section>

        <section class="related-products best-sellers-container">
            <div class="chevrons">
                <i class="fa-solid fa-chevron-left"></i>
                <i class="fa-solid fa-chevron-right"></i>
            </div>
            <div class="best-sellers-box">
                <h2>Related Products</h2>
                <div class="best-seller-slider">
                    <a href="#" class="product-card">
                        <div class="product-img-box">
                            <img src="../storage/uploads/products/placeholder-camera.png" alt="Product Image">
                        </div>
                        <div class="product-description">
                            <h4 class="product-name">Product Name</h4>
                            <span class="brand-name">Brand Name</span>
                            <div class="product-price">
                                <span class="old-price">10,000,000 MMK</span>
                                <span class="new-price">10,000,000 MMK</span>
                            </div>
                            <div class="discount-box">Save 10,000,000 MMK</div>
                            <div class="description-buttons">
                                <button class="buy-now">Buy Now</button>
                                <button class="add-to-cart">Add To Cart</button>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="product-card">
                        <div class="product-img-box">
                            <img src="../storage/uploads/products/placeholder-camera.png" alt="Product Image">
                        </div>
                        <div class="product-description">
                            <h4 class="product-name">Product Name</h4>
                            <span class="brand-name">Brand Name</span>
                            <div class="product-price">
                                <span class="old-price">10,000,000 MMK</span>
                                <span class="new-price">10,000,000 MMK</span>
                            </div>
                            <div class="discount-box">Save 10,000,000 MMK</div>
                            <div class="description-buttons">
                                <button class="buy-now">Buy Now</button>
                                <button class="add-to-cart">Add To Cart</button>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="product-card">
                        <div class="product-img-box">
                            <img src="../storage/uploads/products/placeholder-camera.png" alt="Product Image">
                        </div>
                        <div class="product-description">
                            <h4 class="product-name">Product Name</h4>
                            <span class="brand-name">Brand Name</span>
                            <div class="product-price">
                                <span class="old-price">10,000,000 MMK</span>
                                <span class="new-price">10,000,000 MMK</span>
                            </div>
                            <div class="discount-box">Save 10,000,000 MMK</div>
                            <div class="description-buttons">
                                <button class="buy-now">Buy Now</button>
                                <button class="add-to-cart">Add To Cart</button>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="product-card">
                        <div class="product-img-box">
                            <img src="../storage/uploads/products/placeholder-camera.png" alt="Product Image">
                        </div>
                        <div class="product-description">
                            <h4 class="product-name">Product Name</h4>
                            <span class="brand-name">Brand Name</span>
                            <div class="product-price">
                                <span class="old-price">10,000,000 MMK</span>
                                <span class="new-price">10,000,000 MMK</span>
                            </div>
                            <div class="discount-box">Save 10,000,000 MMK</div>
                            <div class="description-buttons">
                                <button class="buy-now">Buy Now</button>
                                <button class="add-to-cart">Add To Cart</button>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>
    
    <section class="why-choose">
            <h3>Why Smart Vloggers Choose ZYPP Camera House?</h3>
            <div class="why-list">
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="why-text">
                        <h4>Title</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="why-text">
                        <h4>Title</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="why-text">
                        <h4>Title</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="why-text">
                        <h4>Title</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
            </div>
    </section>
    </main>

    <?php include('./footer.php') ?>
    
    <script src="./assets/js/product-details.js"></script>
</body>

</html>
