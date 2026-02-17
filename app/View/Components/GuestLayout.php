<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public $wide;

    /**
     * Create a new component instance.
     */
    public function __construct($wide = false)
    {
        $this->wide = $wide;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
