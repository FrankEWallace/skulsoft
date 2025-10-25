<?php

namespace App\Actions\Config\Module;

class StoreInventoryConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
            'stock_requisition_number_prefix' => 'sometimes|max:200',
            'stock_requisition_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'stock_requisition_number_suffix' => 'sometimes|max:200',
            'stock_purchase_number_prefix' => 'sometimes|max:200',
            'stock_purchase_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'stock_purchase_number_suffix' => 'sometimes|max:200',
            'stock_return_number_prefix' => 'sometimes|max:200',
            'stock_return_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'stock_return_number_suffix' => 'sometimes|max:200',
            'stock_transfer_number_prefix' => 'sometimes|max:200',
            'stock_transfer_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'stock_transfer_number_suffix' => 'sometimes|max:200',
            'stock_adjustment_number_prefix' => 'sometimes|max:200',
            'stock_adjustment_number_digit' => 'sometimes|required|integer|min:0|max:9',
            'stock_adjustment_number_suffix' => 'sometimes|max:200',
        ], [], [
            'stock_requisition_number_prefix' => __('inventory.stock_requisition.config.props.number_prefix'),
            'stock_requisition_number_digit' => __('inventory.stock_requisition.config.props.number_digit'),
            'stock_requisition_number_suffix' => __('inventory.stock_requisition.config.props.number_suffix'),
            'stock_purchase_number_prefix' => __('inventory.stock_purchase.config.props.number_prefix'),
            'stock_purchase_number_digit' => __('inventory.stock_purchase.config.props.number_digit'),
            'stock_purchase_number_suffix' => __('inventory.stock_purchase.config.props.number_suffix'),
            'stock_return_number_prefix' => __('inventory.stock_return.config.props.number_prefix'),
            'stock_return_number_digit' => __('inventory.stock_return.config.props.number_digit'),
            'stock_return_number_suffix' => __('inventory.stock_return.config.props.number_suffix'),
            'stock_transfer_number_prefix' => __('inventory.stock_transfer.config.props.number_prefix'),
            'stock_transfer_number_digit' => __('inventory.stock_transfer.config.props.number_digit'),
            'stock_transfer_number_suffix' => __('inventory.stock_transfer.config.props.number_suffix'),
            'stock_adjustment_number_prefix' => __('inventory.stock_adjustment.config.props.number_prefix'),
            'stock_adjustment_number_digit' => __('inventory.stock_adjustment.config.props.number_digit'),
            'stock_adjustment_number_suffix' => __('inventory.stock_adjustment.config.props.number_suffix'),
        ]);

        return $input;
    }
}
