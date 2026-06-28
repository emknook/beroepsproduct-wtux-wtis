<?php

function escapeHtml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function renderProduct(array $product): string
{
    $name = escapeHtml($product['name']);
    $ingredients = escapeHtml(implode(', ', $product['ingredients']));
    $price = number_format((float) $product['price'], 2, ',', '.');

    return '
        <div class="product">
            <div class="product-name">' . $name . '</div>
            <div class="product-ingredients">' . $ingredients . '</div>
            <div class="product-price">€' . $price . '</div>

            <form method="post" action="index.php" class="product-cart-form product-to-cart">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="product_name" value="' . $name . '">

                <button class="button" type="submit">
                    Add to cart
                </button>
            </form>
        </div>
    ';
}

function renderProducts(array $products): string
{
    if ($products === []) {
        return '<p>No products found.</p>';
    }

    $html = '';

    foreach ($products as $product) {
        $html .= renderProduct($product);
    }

    return $html;
}

function renderType(array $type, ?bool $selected = false): string
{
    $name = escapeHtml($type['name']);
    return '<a class="type ' . ($selected ? 'selected' : '') . '" href="index.php?type=' . $name . '">' . $name . '</a>';
}

function renderTypes(array $types, ?string $selectedType = null): string
{
    $html = '<a class="type" href="index.php?type=">Alles</a>';

    if ($types === []) {
        return $html;
    }

    foreach ($types as $type) {
        $html .= renderType($type, $selectedType === $type['name']);
    }

    return $html;
}

function renderCartButton(int $cartAmount): string
{
    $cart = $_SESSION['cart'] ?? [];
    $total = 0;

    $html = '
        <div class="expandable-button">
            <a class="expandable-button__label" href="order.php">
                Current order (' . $cartAmount . ')
            </a>

            <div class="expandable-panel">
                <div class="cart-items">
    ';

    if ($cart === []) {
        $html .= '
            <p class="cart-empty">
                Your cart is empty.
            </p>
        ';
    }

    foreach ($cart as $item) {
        $name = escapeHtml($item['name']);
        $price = (float) $item['price'];
        $amount = (int) $item['amount'];

        $rowTotal = $price * $amount;
        $total += $rowTotal;

        $formattedPrice = number_format($rowTotal, 2, ',', '.');

        $html .= '
            <div class="cart-row">
                <span class="cart-product-name">' . $name . '</span>
                <span class="cart-product-price">€' . $formattedPrice . '</span>

                <div class="cart-amount">
                    <form method="post" action="index.php" class="cart-amount-form">
                        <input type="hidden" name="action" value="remove_from_cart">
                        <input type="hidden" name="product_name" value="' . $name . '">
                        <button type="submit">-</button>
                    </form>

                    <span>' . $amount . '</span>

                    <form method="post" action="index.php" class="cart-amount-form">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="product_name" value="' . $name . '">
                        <button type="submit">+</button>
                    </form>
                </div>
            </div>
        ';
    }

    $formattedTotal = number_format($total, 2, ',', '.');

    $html .= '
                </div>

                <div class="cart-total">
                    <span>Total</span>
                    <span>€' . $formattedTotal . '</span>
                </div>

                <div class="expandable-panel-actions">
                    <form method="post" action="order.php" class="cart-empty-form">
                        <input type="hidden" name="action" value="empty_cart">
                        <button type="submit">Empty cart</button>
                    </form>

                    <a class="button" href="order.php">Place order</a>
                </div>
            </div>
        </div>
    ';

    return $html;
}

function renderAccountButton(bool $isSignedIn): string
{
    if ($isSignedIn) {
        $username = escapeHtml($_SESSION['user']['username'] ?? 'Account');
        $firstName = escapeHtml($_SESSION['user']['first_name'] ?? '');

        $label = $firstName !== '' ? $firstName : $username;

        return '
            <div class="expandable-button">
                <a class="expandable-button__label" href="#">
                    ' . $label . '
                </a>

                <div class="expandable-panel">
                    <div class="account-panel">
                        <p class="account-panel__name">
                            Signed in as ' . $username . '
                        </p>

                        <div class="expandable-panel-actions">
                            <a href="./profile.php">Profile & order history</a>

                            <form method="post" action="calls/logout.php" class="signout-form">
                                <button class="button" type="submit">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    return '
        <div class="expandable-button">
            <a class="expandable-button__label" href="#">
                Account
            </a>

            <div class="expandable-panel">
                <form method="post" action="calls/login.php" class="signin-form">
                    <div class="signin">
                        <label for="username">Username</label>
                        <input id="username" name="username" class="text-input" required>

                        <label for="password">Password</label>
                        <input id="password" name="password" class="text-input" type="password" required>
                    </div>

                    <div class="expandable-panel-actions">
                        <a href="./register.php">Register</a>
                        <button class="button" type="submit">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    ';
}


function renderStatusButton(int $orderId, int $currentStatus, int $stepStatus, string $label): string
{
    $processedClass = isStatusProcessed($currentStatus, $stepStatus);

    if ($currentStatus === 10 || $currentStatus === 5) {
        return '
            <div class="order-actions-button' . $processedClass . '" title="' . escapeHtml($label) . '">
                <span>' . $stepStatus . '</span>
            </div>
        ';
    }

    return '
        <form method="post" class="order-actions-button ' . $processedClass . '" title="' . escapeHtml($label) . '">
            <input type="hidden" name="action" value="set_status">
            <input type="hidden" name="order_id" value="' . $orderId . '">
            <input type="hidden" name="status" value="' . $stepStatus . '">

            <button type="submit">
                <span>' . renderOrderButtonSvg($stepStatus) . '</span>
            </button>
        </form>
    ';
}

function renderOrderButtonSvg($stepStatus)
{
    switch ($stepStatus) {
        case 1:
            return '<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M468.166 24.156c-13.8-.31-30.977 9.192-42.46 16.883-22.597 15.13-45.255 67.882-45.255 67.882s-17.292-5.333-22.626 0c-5.333 5.333 0 22.627 0 22.627l-4.95 4.948 22.628 22.63 4.95-4.952s17.293 5.333 22.626 0c5.333-5.334 0-22.627 0-22.627s52.75-22.66 67.883-45.255c10.7-15.978 24.91-42.97 11.313-56.568-3.824-3.825-8.707-5.45-14.107-5.57zM312.568 121.65L121.65 312.568l77.782 77.782L390.35 199.432l-77.782-77.782zm-176.07 231.223l-4.95 4.95s-17.293-5.332-22.626 0c-5.333 5.335 0 22.628 0 22.628s-52.75 22.66-67.883 45.255c-10.7 15.978-24.91 42.97-11.313 56.568 13.597 13.598 40.59-.612 56.568-11.312 22.596-15.13 45.254-67.882 45.254-67.882s17.292 5.333 22.626 0c5.333-5.333 0-22.627 0-22.627l4.95-4.948-22.628-22.63z"/></svg>';
        case 2:
            return '
                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 463 463" xml:space="preserve">
                            <path d="M454.932,293.904c-1.406-57.604-24.501-111.55-65.394-152.442C347.325,99.248,291.199,76,231.5,76
                                S115.675,99.248,73.461,141.462C32.57,182.354,9.474,236.3,8.068,293.904C3.265,296.54,0,301.645,0,307.5v64
                                c0,8.547,6.953,15.5,15.5,15.5h432c8.547,0,15.5-6.953,15.5-15.5v-64C463,301.645,459.736,296.54,454.932,293.904z M359,332v-25h65
                                v25H359z M15,332v-24.5c0-0.276,0.224-0.5,0.5-0.5H24v25H15z M36.127,226.692l30.502,12.635C60.572,255.87,56.95,273.573,56.171,292
                                H23.149C23.963,269.108,28.48,247.145,36.127,226.692z M78.939,157.546l23.337,23.337c-12.104,13.175-22.219,28.201-29.878,44.597
                                l-30.515-12.64C51.249,192.429,63.818,173.786,78.939,157.546z M144.84,109.884l12.639,30.515
                                c-16.396,7.659-31.422,17.774-44.597,29.878l-23.337-23.337C105.786,131.818,124.429,119.249,144.84,109.884z M373.455,146.939
                                l-23.337,23.337c-13.176-12.104-28.201-22.219-44.597-29.878l12.64-30.515C338.571,119.249,357.214,131.818,373.455,146.939z
                                M421.117,212.84l-30.515,12.64c-7.659-16.396-17.774-31.422-29.878-44.597l23.337-23.337
                                C399.183,173.786,411.751,192.429,421.117,212.84z M439.851,292H406.83c-0.779-18.427-4.401-36.131-10.458-52.674l30.502-12.635
                                C434.521,247.144,439.037,269.108,439.851,292z M279,332v-25h65v25H279z M199,332v-25h65v25H199z M119,332v-25h65v25H119z
                                M270.542,292c-2.195-17.495-11.932-26.852-19.947-34.545c-5.436-5.218-10.13-9.725-11.871-15.969
                                c-0.735-2.636-2.847-4.663-5.511-5.288c-2.663-0.623-5.457,0.25-7.288,2.285c-0.974,1.082-22.11,24.898-25.464,53.517h-41.245
                                c2.297-40.897,23.356-58.51,42.068-74.141c11.881-9.924,23.249-19.419,27.72-33.275c8.426,7.782,14.557,18.681,18.538,27.482
                                c5.989,13.242,8.606,24.929,8.631,25.039c0.621,2.834,2.821,5.056,5.649,5.705c2.827,0.648,5.776-0.392,7.57-2.671
                                c0.405-0.514,7.574-9.701,12.889-21.656c10.349,13.642,27.157,40.528,29.448,73.517H270.542z M255.372,292h-39.775
                                c2.051-13.695,9.151-26.376,14.641-34.465c2.966,4.018,6.5,7.41,9.97,10.741C247.229,275.016,253.443,281.011,255.372,292z
                                M326.77,292c-3.245-53.825-40.277-92.086-41.923-93.759c-1.925-1.958-4.767-2.705-7.406-1.953c-2.64,0.754-4.657,2.89-5.258,5.569
                                c-1.245,5.543-3.671,11.092-6.192,15.829c-6.006-16.751-18.095-41.897-39.016-52.833c-2.326-1.215-5.117-1.127-7.359,0.232
                                c-2.244,1.359-3.615,3.792-3.615,6.415c0,14.523-10.206,23.048-24.332,34.848C171.458,223.23,146.562,244.063,144.2,292H71.181
                                c3.931-85.031,74.334-153,160.319-153c85.986,0,156.388,67.969,160.32,153H326.77z M291.674,134.629
                                c-16.543-6.057-34.247-9.679-52.674-10.458V91.15c22.892,0.814,44.856,5.33,65.308,12.977L291.674,134.629z M224,124.171
                                c-18.427,0.779-36.131,4.401-52.674,10.458l-12.634-30.502C179.145,96.48,201.108,91.963,224,91.15V124.171z M39,307h65v25H39V307z
                                M144,347v25H79v-25H144z M159,347h65v25h-65V347z M239,347h65v25h-65V347z M319,347h65v25h-65V347z M439,307h8.5
                                c0.276,0,0.5,0.224,0.5,0.5V332h-9V307z M15,371.5V347h49v25H15.5C15.224,372,15,371.776,15,371.5z M447.5,372H399v-25h49v24.5
                                C448,371.776,447.776,372,447.5,372z"/>
                        </svg>';
        case 3:
            return '<svg version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512"  xml:space="preserve">
                        <g>
                            <path class="st0" d="M436.94,75.059C388.56,26.657,324.311,0,255.979,0C187.696,0,123.441,26.657,75.06,75.059
                                C26.651,123.454-0.006,187.709,0,256c-0.006,68.29,26.651,132.545,75.06,180.934C123.441,485.343,187.689,512,256,512l0.021-2.103
                                l0.013,2.103c68.277,0,132.526-26.657,180.906-75.066C485.349,388.545,512.013,324.291,512,256
                                C512.013,187.709,485.349,123.454,436.94,75.059z M419.292,419.293C375.597,462.974,317.597,487.042,256,487.049
                                c-61.596-0.007-119.59-24.074-163.292-67.756C49.026,375.597,24.959,317.603,24.952,256c0.007-61.604,24.074-119.598,67.756-163.3
                                C136.416,49.019,194.403,24.958,256,24.951c61.597,0.007,119.584,24.068,163.293,67.749c43.682,43.702,67.756,101.696,67.756,163.3
                                C487.048,317.603,462.974,375.597,419.292,419.293z"/>
                            <path class="st0" d="M256,66.838C151.53,66.845,66.846,151.523,66.838,256C66.846,360.477,151.53,445.155,256,445.162
                                C360.484,445.155,445.162,360.477,445.162,256C445.162,151.523,360.484,66.845,256,66.838z M374.884,374.878
                                c-30.459,30.439-72.428,49.231-118.885,49.238c-46.456-0.007-88.418-18.799-118.871-49.238
                                C106.684,344.418,87.884,302.457,87.884,256c0.007-46.457,18.799-88.418,49.244-118.878C167.582,106.683,209.544,87.891,256,87.884
                                c46.457,0.007,88.426,18.799,118.885,49.238c30.432,30.46,49.231,72.421,49.231,118.878
                                C424.116,302.457,405.316,344.418,374.884,374.878z"/>
                            <polygon class="st0" points="318.104,165.053 317.076,132.107 294.879,132.416 295.907,165.362 	"/>
                            <polygon class="st0" points="129.011,215.6 172.788,237.947 182.969,218.018 139.192,195.657 	"/>
                            <path class="st0" d="M149.948,288.85c-11.456,0-20.745,9.283-20.745,20.744c0,11.455,9.29,20.745,20.745,20.745
                                c11.461,0,20.751-9.29,20.751-20.745C170.699,298.133,161.408,288.85,149.948,288.85z"/>
                            <path class="st0" d="M252.54,152.893c0-15.922-12.908-28.829-28.815-28.829c-15.928,0-28.829,12.908-28.829,28.829
                                c0,15.908,12.901,28.822,28.829,28.822C239.633,181.715,252.54,168.801,252.54,152.893z"/>
                            <polygon class="st0" points="208.167,381.605 240.496,390.806 243.367,369.938 211.044,360.737 	"/>
                            <path class="st0" d="M234.289,260.364c-13.907,0-25.184,11.277-25.184,25.177c0,13.907,11.276,25.185,25.184,25.185
                                c13.907,0,25.178-11.278,25.178-25.185C259.467,271.641,248.196,260.364,234.289,260.364z"/>
                            <path class="st0" d="M322.516,320.467c-15.462,0-27.993,12.531-27.993,27.987c0,15.462,12.53,27.993,27.993,27.993
                                c15.456,0,27.98-12.53,27.98-27.993C350.496,332.998,337.972,320.467,322.516,320.467z"/>
                            <path class="st0" d="M343.082,241.442c14.003,0,25.363-11.352,25.363-25.356c0-14.004-11.36-25.355-25.363-25.355
                                c-14.004,0-25.362,11.352-25.362,25.355C317.72,230.089,329.079,241.442,343.082,241.442z"/>
                            <polygon class="st0" points="387.956,271.298 359.703,290.495 366.266,307.149 394.533,287.959 	"/>
                        </g>
                        </svg>';
        case 4:
            return '<svg viewBox="0 0 1000 1000" id="Layer_2" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M162.035,648.914c-1.491,6.33-2.282,12.926-2.282,19.707c0,47.333,38.363,85.706,85.697,85.706  c45.511,0,82.743-35.484,85.531-80.296l-28.934-4.287c-0.589,30.709-25.759,55.503-56.597,55.503  c-31.215,0-56.616-25.401-56.616-56.625c0-5.345,0.745-10.507,2.125-15.41L162.035,648.914z"/><path d="M754.298,646.528c-4.241-2.729-9.289-4.312-14.708-4.312c-7.715,0-14.683,3.208-19.642,8.361  c-3.069,3.193-5.37,7.133-6.598,11.52c-0.656,2.341-1.007,4.811-1.007,7.363c0,15.049,12.198,27.247,27.247,27.247  c15.046,0,27.244-12.198,27.244-27.247c0-2.552-0.351-5.022-1.007-7.363C764.007,655.602,759.839,650.087,754.298,646.528z"/><path d="M272.608,669.457c0-1.408-0.11-2.797-0.313-4.149l-51.482-7.655c-0.69,1.417-1.251,2.898-1.684,4.444  c-0.653,2.346-1.003,4.811-1.003,7.36c0,15.051,12.19,27.25,27.241,27.25C260.418,696.707,272.608,684.508,272.608,669.457z" /><path d="M794.047,525.35c0.405-34.508-6.863-63.736-31.786-66.91  c-63.984-8.142-183.813-13.957-177.989,27.921c5.814,41.878,57.002,89.579,44.205,112.431  c-12.797,22.843-195.441,21.353-223.363,5.07c-27.921-16.284-61.657-132.626-36.063-204.771  c8.904-25.122,11.597-47.111,11.341-65.13h27.642c2.773,3.201,6.858,5.235,11.426,5.235h51.963c8.353,0,15.124-6.771,15.124-15.123  c0-8.353-6.771-15.124-15.124-15.124h-51.963c-4.568,0-8.653,2.034-11.426,5.235h-29.866c-2.435-8.297-7.083-15.603-13.248-21.277  c-2.051-1.886-4.259-3.588-6.624-5.088c-4.48-24.526-19.964-42.144-38.888-42.144c-22.539,0-40.193,24.987-40.193,56.874  c0,27.296,12.925,49.523,30.782,55.364c-9.219,12.08-21.426,25.668-37.508,40.83c-56.58,53.322-65.706,118.853-65.135,158.338  c-25.747,10.699-46.251,32.458-54.722,60.233c-2.211,7.247,2.565,14.766,10.059,15.882l4.414,0.657l30.507,4.535l16.338,2.429  l38.364,5.704l47.729,7.102l30.479,4.526l4.424,0.658c7.448,1.108,14.246-4.616,14.242-12.146c0-0.047,0-0.094,0-0.141h308.673  c-2.042,7.295-3.137,14.986-3.137,22.935c0,46.873,37.995,84.869,84.869,84.869c46.864,0,84.859-37.996,84.859-84.869  c0-7.949-1.095-15.64-3.137-22.935h1.113c9.521,0,18.04-6.044,21.076-15.079c2.861-8.501,4.407-17.6,4.407-27.066  C847.912,568.452,825.583,537.733,794.047,525.35z M325.912,333.506c-2.107,1.886-4.343,2.916-6.505,2.916  c-8.114,0-17.194-14.49-17.194-33.874c0-19.384,9.08-33.875,17.194-33.875c7.995,0,16.937,14.086,17.186,33.056  c0.009,0.267,0.009,0.543,0.009,0.819C336.602,316.762,331.717,328.345,325.912,333.506z M739.593,725.447  c-30.874,0-55.991-25.116-55.991-55.99c0-8.16,1.757-15.925,4.913-22.935h0.865c-0.156-0.276-0.322-0.562-0.478-0.837  c8.96-19.016,28.317-32.209,50.691-32.209c15.088,0,28.795,5.989,38.87,15.732c5.087,4.913,9.246,10.782,12.19,17.314  c3.165,7.01,4.921,14.775,4.921,22.935C795.574,700.331,770.458,725.447,739.593,725.447z"/><path d="M450.634,597.496c16.845,1.693,38.74,2.898,64.822,2.898c55.797,0,90.361-5.75,99.432-10.286  c-0.046-1.444-0.534-4.535-2.98-10.221c-3.202-7.397-8.399-16.173-13.975-25.493c-32.696,24.223-83.415,18.768-111.383,20.663  C466.063,576.446,455.786,587.165,450.634,597.496z"/><path d="M770.154,444.051l-11.564-15.75c-7.415-10.093-18.859-16.422-31.344-17.342  c-20.92-1.545-54.399-2.787-81.216,1.877c-24.417,4.25-35.972,19.826-41.372,32.246c17.323-5.41,41.049-8.068,71.529-8.068  c36.192,0,70.701,3.707,88.052,5.915C766.19,443.177,768.167,443.545,770.154,444.051z"/></svg>';
    }
}

function renderAcceptDenyActions(int $orderId, int $status): string
{
    if ($status !== 0) {
        return '';
    }

    return '
        <div class="order-actions">
            <form method="post" class="order-actions-button">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="order_id" value="' . $orderId . '">
                <input type="hidden" name="status" value="1">

                <button type="submit" title="Accept order">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.0303 8.78039L8.99993 16.8107L5.4696 13.2804L6.53026 12.2197L8.99993 14.6894L15.9696 7.71973L17.0303 8.78039Z"/>
                    </svg>
                </button>
            </form>

            <form method="post" class="order-actions-button">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="order_id" value="' . $orderId . '">
                <input type="hidden" name="status" value="10">

                <button type="submit" title="Deny order">
                    <svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                        <path d="M697.4 759.2l61.8-61.8L573.8 512l185.4-185.4-61.8-61.8L512 450.2 326.6 264.8l-61.8 61.8L450.2 512 264.8 697.4l61.8 61.8L512 573.8z"/>
                    </svg>
                </button>
            </form>
        </div>
    ';
}