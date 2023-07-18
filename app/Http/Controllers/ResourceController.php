<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;

abstract class ResourceController extends BaseController
{
    protected $defaultOptions = [
        'sort' => 0,
        'order' => 1,
        'paginate' => true,
        'appends' => ['sort', 'order'],
        'per_page' => 10
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function query($model, $options = [], callable $filter = null)
    {
        $options = array_merge($this->defaultOptions, $options);

        $options['sort'] = limit($options['sort'], $options['sort_min'], $options['sort_max']);
        $options['order'] = limit($options['order'], 1, 2);

        $field = $options['field_names'][$options['sort'] - 1];
        $order = $options['order'] == 1 ? 'asc' : 'desc';

        $model = 'App\\' . $model;
        $query = $model::orderBy($field, $order);

        if ($filter != null) {
            $query = $filter($query, $options);
        }

        foreach ($options['appends'] as $key) {
            $appends["$key"] = intval($options["$key"]);
        }

        if ($options['paginate']) {
            $pagination = $query->paginate($options['per_page'])->appends($appends);

            $data = [
                'reverse' => 3 - $options['order'],
                'page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
                'from' => $pagination->firstItem(),
                'to' => $pagination->lastItem(),
                'items' => $pagination->items(),
                'pagination' => $pagination->render()->render(),
                'index' => $this->generateIndex($pagination)
            ];
        } else {
            $data = ['items' => $query->get()];
        }

        return array_merge($appends, $data);
    }

    protected function queryWithUnion($model, $options = [], callable $filter = null)
    {
        $page = Request::input('page', 1);
        $options = array_merge($this->defaultOptions, $options);

        $options['sort'] = limit($options['sort'], $options['sort_min'], $options['sort_max']);
        $options['order'] = limit($options['order'], 1, 2);

        $field = $options['field_names'][$options['sort'] - 1];
        $order = $options['order'] == 1 ? 'asc' : 'desc';

        $model = 'App\\' . $model;
        $query = $model::orderBy($field, $order);

        if ($filter != null) {
            $query = $filter($query, $options);
        }

        foreach ($options['appends'] as $key) {
            $appends["$key"] = intval($options["$key"]);
        }

        if ($options['paginate']) {
            $res = $query->get();
            $res->sortBy($field, SORT_REGULAR, $options['order'] == 1 ? false : true);
            $slice = array_slice($res->toArray(), $options['per_page'] * ($page - 1), $options['per_page']);
            $items_slice = $res->slice($options['per_page'] * ($page - 1), $options['per_page']);
            $pagination = new LengthAwarePaginator($slice, count($res), $options['per_page']);
            $pagination = $pagination->appends($appends);
            $currentURL = Request::url();
            $pagination = $pagination->setPath($currentURL);

            $data = [
                'reverse' => 3 - $options['order'],
                'page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
                'from' => $pagination->firstItem(),
                'to' => $pagination->lastItem(),
                'items' => $items_slice->all(),
                'pagination' => $pagination->render()->render(),
                'index' => $this->generateIndex($pagination)
            ];
        } else {
            $data = ['items' => $query->get()];
        }

        return array_merge($appends, $data);
    }

    private function generateIndex($pagination)
    {
        return $pagination->firstItem() . ' - ' . $pagination->lastItem() . ' of ' . $pagination->total();
    }
}
