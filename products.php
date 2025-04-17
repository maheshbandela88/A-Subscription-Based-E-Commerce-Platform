<?php
require 'dbcon.php';
$category = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($category) {
    $result = mysqli_query($conn, "SELECT * FROM `category` WHERE `cid` = $category");
    $row = $result->fetch_assoc();
    $category_name = $row['name'];
    $title = ucwords($category_name).' products';
    $result = mysqli_query($conn, "SELECT * FROM `product` WHERE `cid` = $category");
} else {
    $title = 'All products';
    $result = mysqli_query($conn, 'SELECT * FROM `product` LIMIT 20');
}
?>
<!--
author: W3layouts
author URL: http://w3layouts.com
License: Creative Commons Attribution 3.0 Unported
License URL: http://creativecommons.org/licenses/by/3.0/
-->
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?> | Grocery Store</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- CSS -->
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
    <link href="css/style.css" rel="stylesheet" type="text/css" media="all" />
    <link href="css/font-awesome.css" rel="stylesheet" type="text/css" media="all" />
    <!-- JavaScript -->
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/move-top.js"></script>
    <script src="js/easing.js"></script>
    <style>
        .product-price {
            flex-wrap: wrap;
          gap: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'?>
    <div class="w3l_banner_nav_right">
        <div class="w3l_banner_nav_right_banner4">
            <h3>Best Deals For New Products<span class="blink_me"></span></h3>
        </div>
        <div class="w3ls_w3l_banner_nav_right_grid w3ls_w3l_banner_nav_right_grid_sub">
            <h3><?php echo $title; ?></h3>
            <div class="w3ls_w3l_banner_nav_right_grid1">
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($product = $result->fetch_assoc()) {
                        // Ensure all variables are properly defined
                        $pid = isset($product['pid']) ? $product['pid'] : '';
                        $name = isset($product['name']) ? $product['name'] : '';
                        $weight = isset($product['weight']) ? trim($product['weight'], '()') : '';
                        $pic = isset($product['pic']) ? $product['pic'] : '';
                        $price = isset($product['price']) ? $product['price'] : 0;
                        $discount = isset($product['discount']) ? $product['discount'] : 0;
                        $discount_money = $price * ($discount / 100);
                        $new_price = $discount == 0 ? $price : $price * (1 - ($discount / 100));
                ?>
                        <div class="col-md-3 top_brand_left" style="margin-bottom:15px">
                            <div class="hover14 column">
                                <div class="agile_top_brand_left_grid">
                                    <div class="tag">
                                        <img src="images/tag.png" alt="" class="img-responsive">
                                    </div>
                                    <div class="agile_top_brand_left_grid1">
                                        <figure>
                                            <div class="snipcart-item block">
                                                <div class="snipcart-thumb">
                                                    <a href="single.php?id=<?php echo $pid; ?>">
                                                        <img title="<?php echo $name; ?>" alt="<?php echo $name; ?>" src="<?php echo $pic; ?>" width="140">
                                                    </a>		
                                                    <p><?php echo htmlspecialchars($name.($weight ? " ($weight)" : '')); ?></p>
                                                    <div class="product-price-container">
                                                        <div class="price-wrapper d-flex align-items-center gap-2">
                                                            <span class="current-price fw-bold fs-5">
                                                                ₹<?php echo number_format($new_price, 2); ?>
                                                            </span>
                                                            <?php if ($discount > 0): ?>
                                                                <span class="original-price text-muted text-decoration-line-through">
                                                                    ₹<?php echo number_format($price, 2); ?>
                                                                </span>
                                                                <span class="discount-badge badge bg-success rounded-pill">
                                                                    -<?php echo $discount; ?>%
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="snipcart-details top_brand_home_details">
                                                    <form action="checkout.php" method="post">
                                                        <fieldset>
                                                            <input type="hidden" name="cmd" value="_cart" />
                                                            <input type="hidden" name="add" value="1" />
                                                            <input type="hidden" name="business" value="" />
                                                            <input type="hidden" name="item_name" value="<?php echo $name; ?>" />
                                                            <input type="hidden" name="amount" value="<?php echo $price; ?>" />
                                                            <input type="hidden" name="discount_amount" value="<?php echo $discount_money; ?>" />
                                                            <input type="hidden" name="currency_code" value="INR" />
                                                            <input type="hidden" name="return" value="" />
                                                            <input type="hidden" name="cancel_return" value="" />
                                                            <input type="submit" name="submit" value="Add to cart" class="button" />
                                                        </fieldset>
                                                    </form>
                                                </div>
                                            </div>
                                        </figure>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                }
                ?>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>

    

    <?php include 'footer.php'?>
</body>
</html>