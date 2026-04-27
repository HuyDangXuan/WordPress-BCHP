<?php

if (! defined('ABSPATH')) {
    exit;
}

function op_travel_get_workflow_steps()
{
    return [
        [
            'number' => '01',
            'label' => 'Chọn tour',
            'description' => 'Khám phá shortlist hành trình theo điểm đến và phong cách du lịch.',
        ],
        [
            'number' => '02',
            'label' => 'Xác nhận giữ chỗ',
            'description' => 'Chốt ngày khởi hành, số lượng khách và yêu cầu riêng cho chuyến đi.',
        ],
        [
            'number' => '03',
            'label' => 'Thanh toán',
            'description' => 'Hoàn thiện thông tin thanh toán với câu chuyện checkout được tối ưu cho tour.',
        ],
        [
            'number' => '04',
            'label' => 'Hoàn tất',
            'description' => 'Theo dõi trạng thái pending, paid, failed, expired hoặc cancelled rõ ràng.',
        ],
    ];
}

function op_travel_render_workflow_steps()
{
    $steps = op_travel_get_workflow_steps();

    echo '<ol class="op-workflow-list">';

    foreach ($steps as $step) {
        echo '<li class="op-workflow-step" data-reveal>';
        echo '<span class="op-workflow-step__number">' . esc_html($step['number']) . '</span>';
        echo '<h3>' . esc_html($step['label']) . '</h3>';
        echo '<p>' . esc_html($step['description']) . '</p>';
        echo '</li>';
    }

    echo '</ol>';
}
