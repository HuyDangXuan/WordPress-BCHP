<?php

namespace OPTravelCore\Support;

final class OrderMeta
{
    const BOOKING_DATA = '_op_travel_booking_data';
    const BOOKING_CODE = '_op_travel_booking_code';
    const PAYMENT_STATE = '_op_travel_payment_state';
    const PAYMENT_CODE = '_op_travel_payment_code';
    const PAYMENT_CHECKOUT_URL = '_op_travel_payment_checkout_url';
    const PAYMENT_QR_URL = '_op_travel_payment_qr_url';
    const PAYMENT_PROVIDER = '_op_travel_payment_provider';
    const PROVIDER_TXN = '_op_travel_provider_transaction_id';
    const PROVIDER_NAME = '_op_travel_payment_provider';

    public static function normalize_booking_snapshot($booking)
    {
        $booking = is_array($booking) ? $booking : [];

        return [
            'departure_date' => sanitize_text_field((string) ($booking['departure_date'] ?? '')),
            'adult_count' => max(1, absint($booking['adult_count'] ?? 1)),
            'child_count' => max(0, absint($booking['child_count'] ?? 0)),
            'customer_note' => sanitize_textarea_field((string) ($booking['customer_note'] ?? '')),
            'tour_code' => sanitize_text_field((string) ($booking['tour_code'] ?? '')),
            'tour_name' => sanitize_text_field((string) ($booking['tour_name'] ?? '')),
            'amount' => self::normalize_amount($booking['amount'] ?? ''),
            'payment_status' => sanitize_text_field((string) ($booking['payment_status'] ?? 'pending')),
        ];
    }

    public static function get_booking_snapshots($order)
    {
        if (! $order || ! is_object($order) || ! method_exists($order, 'get_meta')) {
            return [];
        }

        $raw_value = $order->get_meta(self::BOOKING_DATA, true);

        if (! is_array($raw_value)) {
            return [];
        }

        if (self::looks_like_booking_snapshot($raw_value)) {
            return [self::normalize_booking_snapshot($raw_value)];
        }

        $snapshots = [];

        foreach ($raw_value as $booking) {
            if (! is_array($booking)) {
                continue;
            }

            $snapshots[] = self::normalize_booking_snapshot($booking);
        }

        return $snapshots;
    }

    public static function append_booking_snapshot($order, $booking)
    {
        if (! $order || ! is_object($order) || ! method_exists($order, 'update_meta_data')) {
            return [];
        }

        $snapshots = self::get_booking_snapshots($order);
        $snapshots[] = self::normalize_booking_snapshot($booking);
        $order->update_meta_data(self::BOOKING_DATA, $snapshots);

        return $snapshots;
    }

    public static function update_booking_payment_state($order, $payment_status, $amount = '')
    {
        if (! $order || ! is_object($order) || ! method_exists($order, 'update_meta_data')) {
            return [];
        }

        $snapshots = self::get_booking_snapshots($order);

        if (empty($snapshots)) {
            return [];
        }

        foreach ($snapshots as &$snapshot) {
            $snapshot['payment_status'] = sanitize_text_field((string) $payment_status);

            if ($amount !== '') {
                $snapshot['amount'] = self::normalize_amount($amount);
            }
        }
        unset($snapshot);

        $order->update_meta_data(self::BOOKING_DATA, $snapshots);

        return $snapshots;
    }

    public static function store_payment_reference_data($order, array $payment_data)
    {
        if (! $order || ! is_object($order) || ! method_exists($order, 'update_meta_data')) {
            return;
        }

        if (! empty($payment_data['booking_code'])) {
            $order->update_meta_data(self::BOOKING_CODE, sanitize_text_field((string) $payment_data['booking_code']));
        }

        if (! empty($payment_data['payment_code'])) {
            $order->update_meta_data(self::PAYMENT_CODE, sanitize_text_field((string) $payment_data['payment_code']));
        }

        if (! empty($payment_data['payment_checkout_url'])) {
            $order->update_meta_data(self::PAYMENT_CHECKOUT_URL, esc_url_raw((string) $payment_data['payment_checkout_url']));
        }

        if (! empty($payment_data['payment_qr_url'])) {
            $order->update_meta_data(self::PAYMENT_QR_URL, esc_url_raw((string) $payment_data['payment_qr_url']));
        }

        if (! empty($payment_data['payment_provider'])) {
            $order->update_meta_data(self::PAYMENT_PROVIDER, sanitize_text_field((string) $payment_data['payment_provider']));
        }
    }

    private static function looks_like_booking_snapshot($value)
    {
        return is_array($value)
            && isset($value['departure_date'])
            && isset($value['adult_count'])
            && isset($value['tour_name']);
    }

    private static function normalize_amount($amount)
    {
        if (function_exists('wc_format_decimal')) {
            return wc_format_decimal((string) $amount, 2);
        }

        return (string) $amount;
    }
}
