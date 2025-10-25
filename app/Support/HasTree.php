<?php

namespace App\Support;

trait HasTree
{
    public function getChilds($array, $currentParent = 1, $level = 1, $child = [], $currLevel = 0, $prevLevel = -1): array
    {
        foreach ($array as $categoryId => $category) {
            if ($currentParent === $category) {
                if ($currLevel > $prevLevel) {
                }
                if ($currLevel === $prevLevel) {
                }
                $child[] = $categoryId;
                if ($currLevel > $prevLevel) {
                    $prevLevel = $currLevel;
                }
                $currLevel++;
                if ($level) {
                    $child = $this->getChilds($array, $categoryId, $level, $child, $currLevel, $prevLevel);
                }
                $currLevel--;
            }
        }
        if ($currLevel === $prevLevel) {
        }

        return $child;
    }
}
