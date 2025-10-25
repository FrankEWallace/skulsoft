<?php

namespace App\Mixins;

class RequestMixin
{
    /**
     * Remove a key from request
     *
     * @param  string  $key
     */
    public function remove()
    {
        return function (string|array $key) {
            if (is_array($key)) {
                foreach ($key as $input) {
                    $this->getInputSource()->remove($input);
                }

                return;
            }

            return $this->getInputSource()->remove($key);
        };
    }

    /**
     * Merge data with validated data and remove given keys
     */
    public function secured()
    {
        return function () {
            return $this->safe()
                ->merge($this->input('merge_inputs', []))
                ->except($this->input('remove_inputs', []));
        };
    }
}
