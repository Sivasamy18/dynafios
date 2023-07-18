<?php
namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
class BaseController extends Controller
{
    protected $layout = 'layouts/default';
    protected $title = '';
    protected $currentUser;
    protected $requireAuth = false;
    protected $requireAuthOptions = [];
    protected $requireSuperUser = false;
    protected $requireSuperUserOptions = [];

    protected $defaultOptions = [
        'sort' => 0,
        'order' => 1,
        'paginate' => true,
        'appends' => ['sort', 'order'],
        'per_page' => 10
    ];

    public function __construct()
    {
        $this->currentUser = Auth::user();

        if ($this->requireAuth)
            // $this->beforeFilter('auth', $this->requireAuthOptions);
            $this->middleware('auth', $this->requireAuthOptions);


        // if ($this->requireSuperUser)
        //     // $this->beforeFilter('super_user', $this->requireSuperUserOptions);
        //     $this->middleware('super_user', $this->requireSuperUserOptions);
    }

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (!is_null($this->layout)) {
            $this->layout = View::make($this->layout);
        }
    }

    protected function renderView($view, $data = [])
    {
        return View::make($view)->with($data)->render();
    }

    protected function paginateModel($model, $options, $filterFunction = null)
    {
        $field = $this->getSortField($options);
        $order = $this->getSortOrder($options);
        $query = $model::orderBy($field, $order);

        if (is_callable($filterFunction)) {
            call_user_func($filterFunction, [$query, $options]);
        }

        if (is_string($filterFunction)) {
            call_user_func([$this, $filterFunction], [$query, $options]);
        }

        $pagination = $query->paginate($options["per_page"])->appends([
            "filter" => $options["filter"],
            "sort" => $options["sort"],
            "order" => $options["order"]
        ]);

        return [
            "index" => $this->getPaginationIndex($pagination),
            "view" => $this->renderView($options, $pagination),
            "links" => $pagination->render()
        ];
    }

    private function getSortField($options)
    {
        $sort = intval($options["sort"]);
        $fields = $options["fields"];

        if ($sort < 0 || $sort > count($fields)) {
            throw new Exception("Illegal sorting index: {$sort}");
        }

        return $fields[$sort];
    }

    private function getSortOrder($options)
    {
        $order = intval($options["order"]);

        switch ($order) {
            case 1:
                return "asc";
            case 2:
                return "desc";
        }

        throw new Exception("Illegal sorting order: {$order}");
    }

    private function getPaginationIndex($pagination)
    {
        return Lang::get("pagination.index", [
            "from" => $pagination->getFrom(),
            "to" => $pagination->getTo(),
            "total" => $pagination->getTotal()
        ]);
    }

    protected function queryModel($model, $options = [], callable $filter = null)
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

    private function generateIndex($pagination)
    {
        return $pagination->firstItem() . ' - ' . $pagination->lastItem() . ' of ' . $pagination->total();
    }
}
