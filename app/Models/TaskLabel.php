<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLabel extends Model
{
    public $timestamps = false;

    protected $table = 'label_task';

    protected $fillable = ['task_id', 'label_id'];
}
