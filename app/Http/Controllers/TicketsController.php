<?php

namespace App\Http\Controllers;

use App\Group;
use App\Ticket;
use App\Physician;
use App\TicketMessage;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Validations\TicketMessageValidation;
use App\Http\Controllers\Validations\TicketValidation;
use App\User;
use Illuminate\Support\Facades\Auth;
use function App\Start\is_owner;
use function App\Start\is_super_user;

class TicketsController extends ResourceController
{
    protected $requireAuth = true;

    public function getIndex()
    {
        $options = [
            'filter' => Request::input('filter', 2),
            'sort' => Request::input('sort'),
            'order' => 2,
            'sort_min' => 1,
            'sort_max' => 1,
            'appends' => ['sort', 'order', 'filter'],
            'field_names' => ['created_at']
        ];

        $data = $this->query('Ticket', $options, function ($query, $options) {
            $id = Auth::user();
            $user = User::find($id);
            if ($user->hasRole('super-user'))
                $query->where('tickets.user_id', '=', $this->currentUser->id);

            switch ($options['filter']) {
                case 1:
                    $query->where('open', '=', false);
                    break;
                case 2:
                    $query->where('open', '=', true);
                    break;
            }

            return $query;
        });
        if ($this->currentUser->group_id == Group::Physicians) {
            $physician = Physician::where('email', '=', $this->currentUser->email)->first();
            //issue fixes : missing practice id error on help center fixed 
            $physician->practice_id = Request::has("p_id") ? Request::Input("p_id") : 0;
            $data["physician"] = $physician;
        }

        $data['table'] = View::make('tickets/_tickets')->with($data)->render();

        if (Request::ajax()) {
            return Response::json($data);
        }

        return View::make('tickets/index')->with($data);
    }

    public function getCreate()
    {
        if ($this->currentUser->group_id == Group::Physicians) {
            $physician = Physician::where('email', '=', $this->currentUser->email)->first();
            //issue fixes : missing practice id error on help center fixed 
            $physician->practice_id = 0;
            $data["physician"] = $physician;
            return View::make('tickets/create')->with($data)->render();
        } else {
            return View::make('tickets/create');
        }
    }

    public function postCreate()
    {
        $validation = new TicketValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $ticket = new Ticket();
        $ticket->user_id = $this->currentUser->id;
        $ticket->subject = Request::input('subject');
        $ticket->body = Request::input('body');
        $ticket->open = true;

        if (!$ticket->save()) {
            return Redirect::back()->with(['error' => Lang::get('tickets.create_error')]);
        }

        $this->sendReceived($ticket);
        $this->sendNotification($ticket);

        return Redirect::route('tickets.index')->with(['success' => Lang::get('tickets.create_success')]);
    }

    private function sendReceived($ticket)
    {
        $data = [
            'name' => "{$this->currentUser->first_name} {$this->currentUser->last_name}",
            'email' => "{$this->currentUser->email}",
            'url' => route('tickets.show', $ticket->id),
            'type' => EmailSetup::TICKETS_CREATE,
            'with' => [
                'name' => "{$this->currentUser->first_name} {$this->currentUser->last_name}",
                'url' => route('tickets.show', $ticket->id)
            ]
        ];

        EmailQueueService::sendEmail($data);
    }

    private function sendNotification($ticket)
    {
        $data = [
            "name" => "DYNAFIOS Team at Dynafios",
            "email" => "support@dynafiosapp.com",
            "url" => route("tickets.show", $ticket->id),
            'type' => EmailSetup::TICKETS_NOTIFICATION,
            'with' => [
                'url' => route("tickets.show", $ticket->id)
            ]
        ];

        EmailQueueService::sendEmail($data);
    }

    public function getShow($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!is_super_user() && !is_owner($ticket->user_id))
            App::abort(403);

        return View::make('tickets/show')->with(compact('ticket'));
    }

    public function getEdit($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!is_super_user() && !is_owner($ticket->user_id))
            App::abort(403);

        if (!$ticket->open) {
            return Redirect::back()
                ->with(['error' => Lang::get('tickets.edit_closed')])
                ->withInput();
        }

        return View::make('tickets/edit')->with(compact('ticket'));
    }

    public function postEdit($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!is_super_user() && !is_owner($ticket->user_id))
            App::abort(403);

        $validation = new TicketValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $ticket->subject = Request::input('subject');
        $ticket->body = Request::input('body');

        if (!$ticket->save()) {
            return Redirect::back()->with(['error' => Lang::get('tickets.edit_error')]);
        }

        return Redirect::route('tickets.show', $ticket->id)->with([
            'success' => Lang::get('tickets.edit_success')
        ]);
    }

    public function postCreateMessage($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!is_super_user() && !is_owner($ticket->user_id))
            App::abort(403);

        if (!$ticket->open) {
            return Redirect::back()
                ->with(['error' => Lang::get('ticket_message.closed')])
                ->withInput();
        }

        $validation = new TicketMessageValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $ticketMessage = new TicketMessage();
        $ticketMessage->ticket_id = $ticket->id;
        $ticketMessage->user_id = $this->currentUser->id;
        $ticketMessage->body = Request::input('body');

        if (!$ticketMessage->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('ticket_message.create_error')])
                ->withInput();
        }

        if ($ticket->user_id != $this->currentUser->id) {
            $data = [
                'name' => "{$ticket->user->first_name} {$ticket->user->last_name}",
                'email' => "{$ticket->user->email}",
                'url' => URL::route('tickets.show', $ticket->id),
                'type' => EmailSetup::TICKETS_MESSAGE,
                'with' => [
                    'name' => "{$ticket->user->first_name} {$ticket->user->last_name}",
                    'url' => URL::route('tickets.show', $ticket->id)
                ]
            ];

            EmailQueueService::sendEmail($data);
        } else {
            $this->sendMessageNotification($ticket);
        }

        return Redirect::back()->with(['success' => Lang::get('ticket_message.create_success')]);
    }

    private function sendMessageNotification($ticket)
    {
        $data = [
            'name' => "DYNAFIOS Team at Dynafios",
            'email' => "support@dynafiosapp.com",
            "url" => route("tickets.show", $ticket->id),
            'type' => EmailSetup::TICKETS_RESPONSE,
            'with' => [
                'url' => route("tickets.show", $ticket->id)
            ]
        ];

        EmailQueueService::sendEmail($data);
    }

    public function getEditMessage($ticket_id, $message_id)
    {
        $ticket = Ticket::findOrFail($ticket_id);
        $message = TicketMessage::findOrFail($message_id);

        if (!is_super_user() && !is_owner($message->user_id))
            App::abort(403);

        return View::make('tickets/edit_message')->with(compact('ticket', 'message'));
    }

    public function postEditMessage($ticket_id, $message_id)
    {
        $ticket = Ticket::findOrFail($ticket_id);
        $message = TicketMessage::findOrFail($message_id);

        if (!is_super_user() && !is_owner($message->user_id))
            App::abort(403);

        $validation = new TicketMessageValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()->withErrors($validation->messages())->withInput();
        }

        $message->body = Request::input('body');

        if (!$message->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('ticket_message.edit_error')])
                ->withInput();
        }

        return Redirect::route('tickets.show', $ticket->id)->with([
            'success' => Lang::get('ticket_message.edit_success')
        ]);
    }

    public function getOpen($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!is_super_user() && !is_owner($ticket->user_id))
            App::abort(403);

        $ticket->open = true;

        if (!$ticket->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('tickets.open_error')])
                ->withInput();
        }

        return Redirect::back()
            ->with(['success' => Lang::get('tickets.open_success')])
            ->withInput();
    }

    public function getClose($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!is_super_user() && !is_owner($ticket->user_id))
            App::abort(403);

        $ticket->open = false;

        if (!$ticket->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('tickets.close_error')])
                ->withInput();
        }

        return Redirect::back()
            ->with(['success' => Lang::get('tickets.close_success')])
            ->withInput();

    }

    public function getDelete($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!is_super_user() && !is_owner($ticket->user_id))
            App::abort(403);

        $ticket->messages()->delete();
        $ticket->delete();

        return Redirect::route('tickets.index')->with([
            'success' => Lang::get('tickets.delete_success')
        ]);
    }
}
