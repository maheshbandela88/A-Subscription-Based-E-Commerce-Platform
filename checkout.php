<?php
session_start();
require 'dbcon.php';

// Initialize variables
$products = [];
$cart = [];
$total = 0;

if (!isset($_SESSION['products']) || empty($_SESSION['products'])) {
    if (empty($_POST)) {
        header('Location: index.php');
        exit;
    }
    
    // Process POST data
    foreach ($_POST as $key => $val) {
        $array = explode('_', $key);
        
        if (count($array) > 1) {
            $i = array_pop($array);
        } else {
            $i = $array[0];
        }

        $key = implode('_', $array);

        if (is_numeric($i)) {
            $products[$i][$key] = $val;
        } else {
            $cart[$key] = $val;
        }
    }

    $total = $cart['total'];
    $_SESSION['products'] = $products;
    $_SESSION['total'] = $total;
} else {
    $products = $_SESSION['products'];
    $total = $_SESSION['total'];
}

// Process order if user is logged in
if (isset($_SESSION['USER_ID']) && !empty($_SESSION['USER_ID'])) {
    $uid = $_SESSION['USER_ID'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Save order
        $stmt = $conn->prepare("INSERT INTO `ord`(`uid`, `total`) VALUES (?, ?)");
        $stmt->bind_param("id", $uid, $total);
        $stmt->execute();
        
        // Get order ID
        $oid = $conn->insert_id;
        
        // Save order items
        foreach ($products as $pid => $product) {
            $stmt = $conn->prepare("INSERT INTO `order_items`(`oid`, `pid`, `quantity`, `amount`, `subtotal`) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $oid, $pid, $product['quantity'], $product['amount'], $product['subtotal']);
            $stmt->execute();
        }
        
        // Save payment info
        $paymentType = isset($_POST['payment_type']) ? $_POST['payment_type'] : 'COD';
        $stmt = $conn->prepare("INSERT INTO `payment`(`total_amount`, `payment_type`, `oid`, `uid`) 
                              VALUES (?, ?, ?, ?)");
        $stmt->bind_param("dsii", $total, $paymentType, $oid, $uid);
        $stmt->execute();
        
        // Save subscription if selected
        if (isset($_POST['is_subscription']) && $_POST['is_subscription'] == '1') {
            foreach ($products as $pid => $product) {
                $interval = isset($_POST['interval_'.$pid]) ? (int)$_POST['interval_'.$pid] : 7;
                $stmt = $conn->prepare("INSERT INTO `subscriptions`(`uid`, `pid`, `oid`, `quantity`, `delivery_interval`) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiid", $uid, $pid, $oid, $product['quantity'], $interval);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing order: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout | Grocery Store</title>
    <?php include 'header.php'; ?>
    <style>
        .subscription-item {
            padding: 15px;
            margin: 10px 0;
            background: #f8f8f8;
            border-radius: 5px;
        }
        .subscription-terms {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        .subscription-terms ul {
            list-style: disc;
            padding-left: 20px;
            margin: 15px 0;
        }
        .subscription-confirm {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .tab-content {
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
        }
    </style>
</head>
<body>
    <!-- banner -->
    <div class="banner">
        <div class="w3l_banner_nav_right">
            <!-- payment -->
            <div class="privacy about">
                <h3>Pay<span>ment</span></h3>
                <div class="checkout-right">
                    <?php if (!isset($_SESSION['USER_ID']) || empty($_SESSION['USER_ID'])): ?>
                        <div class="col-md-12 address_form_agile">
                            <section class="creditly-wrapper wthree, w3_agileits_wrapper" style="margin-top: 35px">
                                <div class="information-wrapper">
                                    <a href="login.php?page=checkout">
                                        <button class="submit check_out btn-block">Login To Continue</button>
                                    </a>
                                </div>
                            </section>
                        </div>
                        <div class="clearfix"></div>
                    <?php else: ?>
                        <div class="col-md-12 address_form_agile">
                            <section class="creditly-wrapper wthree, w3_agileits_wrapper" style="margin-top: 35px">
                                <div class="information-wrapper">
                                    <button class="submit check_out btn-block">Your order has been placed</button>
                                </div>
                            </section>
                        </div>
                        <div class="clearfix"></div>
                        
                        <!-- Horizontal Tabs -->
                        <div id="parentHorizontalTab">
                            <ul class="resp-tabs-list hor_1">
                                <li>Address</li>
                                <li>Payment</li>
                            </ul>
                            <div class="resp-tabs-container hor_1">
                                <!-- Address Tab -->
                                <div>
                                    <form id="address-form" autocomplete="off">
                                        <h4>Delivery Address</h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Full Name</label>
                                                    <input type="text" name="full_name" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Phone Number</label>
                                                    <input type="tel" name="phone" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Address Line 1</label>
                                            <input type="text" name="address1" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Address Line 2</label>
                                            <input type="text" name="address2" class="form-control">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>City</label>
                                                    <input type="text" name="city" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>State</label>
                                                    <input type="text" name="state" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Postal Code</label>
                                                    <input type="text" name="postal_code" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Special Instructions</label>
                                            <textarea name="instructions" class="form-control" rows="3"></textarea>
                                        </div>
                                        <button type="button" id="address-next" class="btn btn-primary">Continue to Payment</button>
                                    </form>
                                </div>
                                <!-- Payment Tab -->
                                <div>
    <form id="payment-form" method="post">
        <!-- Step 1: One-Time vs Subscription -->
        <div class="form-group">
            <label><input type="radio" name="payment_mode" value="ONETIME" checked> One-Time Payment</label>
            <label style="margin-left: 20px;"><input type="radio" name="payment_mode" value="SUBSCRIPTION"> Subscription</label>
        </div>

        <!-- Step 2: Payment Method Selection -->
        <div id="payment-options">
            <div class="form-group">
                <label><input type="radio" name="payment_type" value="COD" checked> Cash On Delivery</label>
                <label style="margin-left:20px;"><input type="radio" name="payment_type" value="CREDIT"> Credit/Debit Card</label>
                <label style="margin-left:20px;"><input type="radio" name="payment_type" value="NETBANKING"> Netbanking</label>
                <label style="margin-left:20px;"><input type="radio" name="payment_type" value="PAYPAL"> Paypal</label>
                <label style="margin-left:20px;"><input type="radio" name="payment_type" value="UPI"> UPI</label>
            </div>

            <!-- COD Section -->
            <div id="cod-section" class="payment-section">
                <button type="submit" class="btn btn-primary">Confirm Order</button>
            </div>

            <!-- Credit/Debit Card Section -->
            <div id="card-section" class="payment-section" style="display:none;">
                <div><label>Name on Card</label><input class="form-control" type="text" name="name" required></div>
                <div><label>Card Number</label><input class="form-control" type="text" name="number" required></div>
                <div><label>CVV</label><input class="form-control" type="text" name="security-code" required></div>
                <div><label>Expiration Date</label><input class="form-control" type="text" name="expiration" placeholder="MM/YY" required></div>
                <button type="submit" class="btn btn-primary">Pay with Card</button>
            </div>

            <!-- Netbanking Section -->
            <div id="netbanking-section" class="payment-section" style="display:none;">
                <div class="form-group">
                    <label>Account Holder Name</label><input type="text" name="nb_account_holder" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Account Number</label><input type="text" name="nb_account_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>IFSC Code</label><input type="text" name="nb_ifsc" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Select Bank:</label>
                    <select name="bank" class="form-control" style="width:auto;display:inline-block;">
                        <option value="">-- Select Bank --</option>
                        <option value="syndicate">Syndicate Bank</option>
                        <option value="baroda">Bank of Baroda</option>
                        <option value="canara">Canara Bank</option>
                        <option value="icici">ICICI Bank</option>
                        <option value="sbi">State Bank Of India</option>
                        <option value="hdfc">HDFC Bank</option>
                        <option value="axis">Axis Bank</option>
                        <option value="kotak">Kotak Mahindra Bank</option>
                        <option value="yes">Yes Bank</option>
                        <option value="pnb">Punjab National Bank</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-left:10px;">Pay via Netbanking</button>
            </div>

            <!-- Paypal Section -->
            <div id="paypal-section" class="payment-section" style="display:none;">
                <div class="form-group">
                    <label>PayPal Email</label>
                    <input type="email" name="paypal_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <img class="pp-img" src="images/paypal.png" alt="PayPal" style="height:30px;">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-left:10px;">Checkout via PayPal</button>
            </div>

            <!-- UPI Section -->
            <div id="upi-section" class="payment-section" style="display:none;">
                <div class="form-group">
                    <label>UPI ID</label>
                    <input type="text" name="upi_id" class="form-control" placeholder="example@upi" required pattern="^[\w.-]+@[\w.-]+$" title="Please enter a valid UPI ID (e.g. name@bank)">
                </div>
                <button type="submit" class="btn btn-primary">Pay via UPI</button>
            </div>
        </div>

        <!-- Subscription Section -->
        <div id="subscription-section" class="payment-section" style="display:none;">
            <input type="hidden" name="is_subscription" value="1">
            <div class="subscription-summary">
                <h4>Subscription Summary</h4>
                <?php
                if(isset($_SESSION['products']) && !empty($_SESSION['products'])) {
                    foreach($_SESSION['products'] as $pid => $product) {
                        $query = "SELECT * FROM product WHERE pid = $pid";
                        $result = $conn->query($query);
                        if($row = $result->fetch_assoc()) {
                ?>
                    <div class="subscription-item">
                        <h5><?= htmlspecialchars($row['name']) ?></h5>
                        <div class="form-group">
                            <label>Delivery Interval (days):</label>
                            <select name="interval_<?= $pid ?>" class="form-control">
                                <option value="7">Weekly (7 days)</option>
                                <option value="14">Bi-Weekly (14 days)</option>
                                <option value="30">Monthly (30 days)</option>
                            </select>
                        </div>
                        <p>Quantity: <?= $product['quantity'] ?></p>
                    </div>
                <?php
                        }
                    }
                } else {
                    echo '<p class="alert alert-warning">No items in cart for subscription.</p>';
                }
                ?>
            </div>
            <div class="subscription-terms">
                <h4>Subscription Terms</h4>
                <ul>
                    <li>Your subscription will start from the next delivery</li>
                    <li>Payment will be collected on delivery</li>
                    <li>You can modify or cancel your subscription anytime</li>
                    <li>First delivery will be processed immediately</li>
                </ul>
                <div class="subscription-confirm">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="agree_terms" required>
                            I agree to the subscription terms and automatic deliveries
                        </label>
                    </div>
                    <button type="button" class="btn btn-info" style="margin-top: 15px;" onclick="continueToPayment()">Continue to Payment</button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function continueToPayment() {
            document.getElementById('subscription-section').style.display = 'none';
            document.getElementById('payment-options').style.display = '';
            document.querySelector('input[name="payment_type"]:checked').dispatchEvent(new Event('change'));
        }

        // Mode switch: One-Time vs Subscription
        document.querySelectorAll('input[name="payment_mode"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'ONETIME') {
                    document.getElementById('subscription-section').style.display = 'none';
                    document.getElementById('payment-options').style.display = '';
                    document.querySelector('input[name="payment_type"]:checked').dispatchEvent(new Event('change'));
                } else {
                    document.getElementById('payment-options').style.display = 'none';
                    document.getElementById('subscription-section').style.display = '';
                }
            });
        });

        // Payment type switcher
        document.querySelectorAll('input[name="payment_type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-section').forEach(function(section) {
                    section.style.display = 'none';
                });
                const sectionMap = {
                    'COD': 'cod-section',
                    'CREDIT': 'card-section',
                    'NETBANKING': 'netbanking-section',
                    'PAYPAL': 'paypal-section',
                    'UPI': 'upi-section'
                };
                const targetId = sectionMap[this.value];
                if (targetId) {
                    document.getElementById(targetId).style.display = '';
                }
            });
        });

        // On page load
        window.addEventListener('load', function () {
            const selectedMode = document.querySelector('input[name="payment_mode"]:checked').value;
            if (selectedMode === 'ONETIME') {
                document.getElementById('payment-options').style.display = '';
                document.getElementById('subscription-section').style.display = 'none';
                document.querySelector('input[name="payment_type"]:checked').dispatchEvent(new Event('change'));
            } else {
                document.getElementById('payment-options').style.display = 'none';
                document.getElementById('subscription-section').style.display = '';
            }
        });
    </script>
</div>

                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- //payment -->
        </div>
        <div class="clearfix"></div>
    </div>
    <!-- //banner -->

    <?php include 'footer.php'; ?>

    <!-- JavaScript Libraries -->
    <script src="js/easyResponsiveTabs.js"></script>
    <script src="js/creditly.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize horizontal tabs
            $('#parentHorizontalTab').easyResponsiveTabs({
                type: 'default',
                width: 'auto',
                fit: true,
                tabidentify: 'hor_1',
                activate: function(event) {
                    // Prevent switching to payment tab unless address is validated
                    if ($('.resp-tabs-list li.resp-tab-active').index() === 1 && !window.addressValid) {
                        $('#parentHorizontalTab').find('.resp-tabs-list li').eq(0).click();
                    }
                }
            });

            // Prevent direct tab click to payment if address not validated
            $('.resp-tabs-list li').eq(1).on('click', function(e) {
                if (!window.addressValid) {
                    e.preventDefault();
                    $('#address-form')[0].reportValidity();
                }
            });

            // Address form validation and step control
            window.addressValid = false;
            $('#address-next').on('click', function() {
                var form = $('#address-form')[0];
                if (form.checkValidity()) {
                    window.addressValid = true;
                    // Optionally, save address to session via AJAX here
                    $('.resp-tabs-list li').eq(1).click();
                } else {
                    form.reportValidity();
                }
            });
        });
    </script>
</body>
</html>