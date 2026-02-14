<?php

namespace App\Http\Controllers;

class ScopeClassifierController extends Controller
{
    /**
     * Show the Scope Finder page — helps users who don't know which scope an activity belongs to.
     */
    public function index()
    {
        return view('scope_classifier.index');
    }
}
