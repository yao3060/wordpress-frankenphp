<?php


// 在购物车项名称后添加复选框
add_action('woocommerce_after_cart_item_name', 'add_checkbox_to_cart_items', 10, 2);
function add_checkbox_to_cart_items($cart_item, $cart_item_key) {
    echo '<div class="product-select">
        <label>
            <input type="checkbox" name="selected_items[]" value="'.esc_attr($cart_item_key).'" checked />
            '.esc_html__('选择结算', 'your-textdomain').'
        </label>
    </div>';
}


// 处理结算请求
add_action('template_redirect', 'handle_selected_items_submission');
function handle_selected_items_submission() {
    if (function_exists('is_cart') && is_cart() && isset($_POST['proceed_to_checkout'])) {
        // 安全验证
        if (!wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-cart')) {
            wc_add_notice('安全校验失败，请重试。', 'error');
            return;
        }

        $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
        $cart = WC()->cart;

        // 保存原始购物车数据
        WC()->session->set('original_cart', $cart->get_cart());
        WC()->session->set('selected_items', $selected_items);

        // 移除非选中商品
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (!in_array($cart_item_key, $selected_items)) {
                $cart->remove_cart_item($cart_item_key);
            }
        }

        // 跳转到结算页
        wp_redirect(wc_get_checkout_url());
        exit;
    }
}


// 订单完成后恢复购物车
add_action('woocommerce_thankyou', 'restore_original_cart');
add_action('woocommerce_cart_emptied', 'restore_original_cart');
function restore_original_cart() {
    $original_cart = WC()->session->get('original_cart');
    if ($original_cart) {
        $cart = WC()->cart;
        $cart->empty_cart();

        foreach ($original_cart as $key => $item) {
            $cart->cart_contents[$key] = $item;
        }

        $cart->set_session();
        $cart->calculate_totals();
        WC()->session->set('original_cart', null);
        WC()->session->set('selected_items', null);
    }
}

// 返回购物车时恢复
add_action('template_redirect', 'restore_cart_on_return');
function restore_cart_on_return() {
    if (function_exists('is_cart') && is_cart() && WC()->session->get('original_cart')) {
        restore_original_cart();
    }
}


// 添加前端验证
add_action('wp_footer', 'add_cart_selection_validation');
function add_cart_selection_validation() {
    if (function_exists('is_cart') && is_cart()) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('form.woocommerce-cart-form').on('submit', function(e) {
                    if ($('input[name="selected_items[]"]:checked').length === 0) {
                        alert('请至少选择一个商品进行结算。');
                        e.preventDefault();
                    }
                });
            });
        </script>
        <?php
    }
}
