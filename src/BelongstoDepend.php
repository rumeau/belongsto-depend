<?php

namespace Rumeau\BelongstoDepend;

use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;

class BelongstoDepend extends BelongsTo
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'belongsto-depend';

    public $foreignKeyName;

    public $dependsOn;

    public function dependsOn($relationship, $foreignKey)
    {
        $this->dependsOn = Str::lower($relationship);
        $this->foreignKeyName = $foreignKey;
        return $this;
    }

    public function meta()
    {
        $this->meta = parent::meta();
        return array_merge([
            'dependsOn' => $this->dependsOn,
            'foreignKeyName' => $this->foreignKeyName,
            'resourceClass' => $this->resourceClass,
        ], $this->meta);
    }
}
