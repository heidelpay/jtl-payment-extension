<?php
namespace Heidelpay;

class ArrayFilter{


	/*
         * Method to use array_filter recursively
         * @param array $input
         * @return filtered array
         * source: http://php.net/manual/de/function.array-filter.php
         */


    public function array_filter_recursive($input) {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->array_filter_recursive($value);
            }
        }
        return array_filter($input);
    }
}