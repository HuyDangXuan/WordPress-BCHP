<?php

namespace OPTravelCore;

use OPTravelCore\Support\Env;

final class CustomerRegistrationOtp
{
    const OTP_TTL_SECONDS = 600;
    const RATE_LIMIT_SECONDS = 60;
    const MAX_ATTEMPTS = 5;

    public static function boot()
    {
        add_action('wp_loaded', [__CLASS__, 'handle_request']);
        add_action('phpmailer_init', [__CLASS__, 'configure_smtp']);
        add_filter('wp_mail_from', [__CLASS__, 'mail_from']);
        add_filter('wp_mail_from_name', [__CLASS__, 'mail_from_name']);
    }

    public static function configure_smtp($phpmailer)
    {
        $host = trim((string) Env::get('SMTP_HOST'));

        if (! $host || $host === 'smtp.example.com') {
            return;
        }

        $port = absint(Env::get('SMTP_PORT', 587));
        $user = trim((string) Env::get('SMTP_USER'));
        $pass = self::smtp_password($host);

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = $port ?: 587;

        if ($user && $pass && $pass !== 'change-me') {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $user;
            $phpmailer->Password = $pass;
            $phpmailer->SMTPSecure = $phpmailer->Port === 465 ? 'ssl' : 'tls';
        }

        if ($user && is_email($user)) {
            $phpmailer->setFrom($user, wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), false);
        }
    }

    public static function mail_from($from)
    {
        $user = trim((string) Env::get('SMTP_USER'));

        return is_email($user) ? $user : $from;
    }

    public static function mail_from_name($from_name)
    {
        $user = trim((string) Env::get('SMTP_USER'));

        return is_email($user) ? wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES) : $from_name;
    }

    public static function handle_request()
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            return;
        }

        $action = isset($_POST['op_travel_registration_action'])
            ? sanitize_key(wp_unslash($_POST['op_travel_registration_action']))
            : '';

        if ($action === 'send_otp') {
            self::handle_send_otp();
        }

        if ($action === 'verify_otp') {
            self::handle_verify_otp();
        }

        if ($action === 'resend_otp') {
            self::handle_resend_otp();
        }
    }

    public static function get_pending_email($token)
    {
        $payload = self::get_otp_payload($token);

        return is_array($payload) ? (string) ($payload['email'] ?? '') : '';
    }

    private static function handle_send_otp()
    {
        if (! isset($_POST['op_travel_registration_send_otp_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['op_travel_registration_send_otp_nonce']), 'op_travel_registration_send_otp')) {
            wc_add_notice(__('Phiên đăng ký không hợp lệ. Vui lòng thử lại.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if (! is_email($email)) {
            wc_add_notice(__('Vui lòng nhập email hợp lệ để nhận OTP.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        if (email_exists($email)) {
            wc_add_notice(__('Email này đã có tài khoản. Vui lòng đăng nhập.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        if (get_transient(self::rate_key($email))) {
            wc_add_notice(__('Vui lòng chờ 60 giây trước khi gửi lại OTP.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        $token = self::generate_token();
        $otp = self::generate_otp();
        $sent = self::send_otp_email($email, $otp);

        if (! $sent) {
            wc_add_notice(__('Chưa gửi được OTP. Vui lòng kiểm tra cấu hình SMTP hoặc thử lại sau.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        self::store_otp($token, $email, $otp);
        set_transient(self::rate_key($email), 1, self::RATE_LIMIT_SECONDS);

        wc_add_notice(__('OTP đã được gửi tới email của bạn.', 'op-travel-core'), 'success');
        self::redirect_to_register(['otp_token' => $token]);
    }

    private static function handle_resend_otp()
    {
        if (! isset($_POST['op_travel_registration_resend_otp_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['op_travel_registration_resend_otp_nonce']), 'op_travel_registration_resend_otp')) {
            wc_add_notice(__('Phiên gửi lại OTP không hợp lệ. Vui lòng thử lại.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        $token = isset($_POST['otp_token']) ? sanitize_text_field(wp_unslash($_POST['otp_token'])) : '';
        $payload = self::get_otp_payload($token);

        if (! $payload) {
            wc_add_notice(__('OTP đã hết hạn hoặc không tồn tại. Vui lòng gửi lại mã mới.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        $email = sanitize_email((string) ($payload['email'] ?? ''));

        if (! is_email($email) || email_exists($email)) {
            delete_transient(self::otp_key($token));
            wc_add_notice(__('Email này đã có tài khoản hoặc không hợp lệ. Vui lòng đăng nhập.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        if (get_transient(self::rate_key($email))) {
            wc_add_notice(__('Vui lòng chờ 60 giây trước khi gửi lại OTP.', 'op-travel-core'), 'error');
            self::redirect_to_register(['otp_token' => $token]);
        }

        $otp = self::generate_otp();
        $sent = self::send_otp_email($email, $otp);

        if (! $sent) {
            wc_add_notice(__('Chưa gửi lại được OTP. Vui lòng kiểm tra cấu hình SMTP hoặc thử lại sau.', 'op-travel-core'), 'error');
            self::redirect_to_register(['otp_token' => $token]);
        }

        self::store_otp($token, $email, $otp);
        set_transient(self::rate_key($email), 1, self::RATE_LIMIT_SECONDS);

        wc_add_notice(__('OTP mới đã được gửi tới email của bạn.', 'op-travel-core'), 'success');
        self::redirect_to_register(['otp_token' => $token]);
    }

    private static function handle_verify_otp()
    {
        if (! isset($_POST['op_travel_registration_verify_otp_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['op_travel_registration_verify_otp_nonce']), 'op_travel_registration_verify_otp')) {
            wc_add_notice(__('Phiên xác thực OTP không hợp lệ. Vui lòng thử lại.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        $token = isset($_POST['otp_token']) ? sanitize_text_field(wp_unslash($_POST['otp_token'])) : '';
        $payload = self::get_otp_payload($token);

        if (! $payload) {
            wc_add_notice(__('OTP đã hết hạn hoặc không tồn tại. Vui lòng gửi lại mã mới.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        $email = sanitize_email((string) ($payload['email'] ?? ''));
        $otp = isset($_POST['otp_code']) ? preg_replace('/\D+/', '', (string) wp_unslash($_POST['otp_code'])) : '';
        $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        $password_confirm = isset($_POST['password_confirm']) ? (string) wp_unslash($_POST['password_confirm']) : '';

        if (! wp_check_password($otp, (string) ($payload['otp_hash'] ?? ''))) {
            $payload['attempts'] = absint($payload['attempts'] ?? 0) + 1;

            if ($payload['attempts'] >= self::MAX_ATTEMPTS) {
                delete_transient(self::otp_key($token));
                wc_add_notice(__('Bạn đã nhập sai OTP quá số lần cho phép. Vui lòng gửi mã mới.', 'op-travel-core'), 'error');
                self::redirect_to_register();
            }

            set_transient(self::otp_key($token), $payload, self::OTP_TTL_SECONDS);
            wc_add_notice(__('OTP chưa đúng. Vui lòng kiểm tra email và nhập lại.', 'op-travel-core'), 'error');
            self::redirect_to_register(['otp_token' => $token]);
        }

        if (strlen($password) < 8) {
            wc_add_notice(__('Mật khẩu cần có ít nhất 8 ký tự.', 'op-travel-core'), 'error');
            self::redirect_to_register(['otp_token' => $token]);
        }

        if ($password !== $password_confirm) {
            wc_add_notice(__('Mật khẩu nhập lại chưa khớp.', 'op-travel-core'), 'error');
            self::redirect_to_register(['otp_token' => $token]);
        }

        if (! is_email($email) || email_exists($email)) {
            delete_transient(self::otp_key($token));
            wc_add_notice(__('Email này đã có tài khoản hoặc không hợp lệ. Vui lòng đăng nhập.', 'op-travel-core'), 'error');
            self::redirect_to_register();
        }

        $username = self::generate_username($email);
        $customer_id = function_exists('wc_create_new_customer')
            ? wc_create_new_customer($email, $username, $password)
            : wp_create_user($username, $password, $email);

        if (is_wp_error($customer_id)) {
            wc_add_notice($customer_id->get_error_message(), 'error');
            self::redirect_to_register(['otp_token' => $token]);
        }

        delete_transient(self::otp_key($token));
        wp_set_current_user((int) $customer_id);
        wp_set_auth_cookie((int) $customer_id, true);
        $customer = get_userdata((int) $customer_id);

        if ($customer) {
            do_action('wp_login', $customer->user_login, $customer);
        }

        wc_add_notice(__('Tài khoản đã được tạo và đăng nhập thành công.', 'op-travel-core'), 'success');
        wp_safe_redirect(self::account_url());
        exit;
    }

    private static function generate_username($email)
    {
        $base = sanitize_user(current(explode('@', $email)), true);
        $base = $base ?: 'customer';
        $username = $base;
        $suffix = 1;

        while (username_exists($username)) {
            $suffix++;
            $username = $base . $suffix;
        }

        return $username;
    }

    private static function get_otp_payload($token)
    {
        if (! $token) {
            return false;
        }

        $payload = get_transient(self::otp_key($token));

        return is_array($payload) ? $payload : false;
    }

    private static function generate_token()
    {
        return wp_generate_password(32, false, false);
    }

    private static function generate_otp()
    {
        return (string) random_int(100000, 999999);
    }

    private static function store_otp($token, $email, $otp)
    {
        set_transient(self::otp_key($token), [
            'email' => $email,
            'otp_hash' => wp_hash_password($otp),
            'attempts' => 0,
            'created_at' => time(),
        ], self::OTP_TTL_SECONDS);
    }

    private static function send_otp_email($email, $otp)
    {
        return wp_mail(
            $email,
            sprintf(__('[%s] Mã OTP đăng ký tài khoản', 'op-travel-core'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)),
            sprintf(
                __("Mã OTP của bạn là: %1\$s\n\nMã này hết hạn sau 10 phút. Nếu bạn không yêu cầu tạo tài khoản, hãy bỏ qua email này.", 'op-travel-core'),
                $otp
            ),
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }

    private static function smtp_password($host)
    {
        $pass = trim((string) Env::get('SMTP_PASS'));

        if (strtolower($host) === 'smtp.gmail.com') {
            return preg_replace('/\s+/', '', $pass);
        }

        return $pass;
    }

    private static function otp_key($token)
    {
        return 'op_travel_registration_otp_' . sanitize_key($token);
    }

    private static function rate_key($email)
    {
        return 'op_travel_registration_otp_rate_' . md5(strtolower(trim((string) $email)));
    }

    private static function account_url()
    {
        return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/tai-khoan/');
    }

    private static function redirect_to_register($args = [])
    {
        $url = add_query_arg(array_merge(['op_auth' => 'register'], $args), self::account_url()) . '#op-register';
        wp_safe_redirect($url);
        exit;
    }
}
