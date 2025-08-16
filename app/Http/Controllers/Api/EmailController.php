<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicChannelCredentialService;
use App\Mail\GenericEmail;
use App\Models\User;

class EmailController extends Controller
{
    /** ---------------- DRAFTS ---------------- **/
    public function getDrafts()
    {
        return Email::latest()->get();
    }

    public function storeDraft(Request $request)
    {
        $validated = $request->validate([
            'to' => 'nullable|email',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id'
        ]);

        $draft = Email::create($validated);

        return response()->json(['message' => 'Draft saved', 'draft' => $draft], 201);
    }

    public function deleteDraft($id)
    {
        Email::findOrFail($id)->delete();
        return response()->json(['message' => 'Draft deleted']);
    }

    /** ---------------- SENT EMAILS ---------------- **/
    public function getSentEmails()
    {
        return Email::where('status', 'sent')->latest()->get();
    }

    /** ---------------- SINGLE SEND ---------------- **/
    public function sendEmail(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        try {
            $user = User::findOrFail($request->user()->id);

            $credentialService = new DynamicChannelCredentialService($user, 'email');
            $credentials = $credentialService->getCredential();

            config([
                'mail.mailers.dynamic' => [
                    'transport'  => 'smtp',
                    'host'       => $credentials-> 	smtp_host,
                    'port'       => $credentials->port,
                    'encryption' => $credentials->encryption,
                    'username'   => $credentials->username,
                    'password'   => $credentials->password,
                ],
                'mail.from.address' => $credentials->from_address,
                'mail.from.name'    => $credentials->from_name,
            ]);

            Mail::mailer('dynamic')
                ->to($validated['to'])
                ->send(new GenericEmail($validated['subject'], $validated['body']));

            Email::create([
                'to'        => $validated['to'],
                'subject'   => $validated['subject'],
                'body'      => $validated['body'],
                'client_id' => $user->id,
                'status'    => 'sent'
            ]);

            return response()->json(['message' => 'Email sent successfully']);
        } catch (\Throwable $e) {
            Log::error("Email sending failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to send email'], 500);
        }
    }

    /** ---------------- BULK SEND ---------------- **/
    public function sendBulkEmails(Request $request)
    {
        $validated = $request->validate([
            'client_ids'  => 'required|array',
            'template_id' => 'required|exists:email_templates,id'
        ]);

        $template = EmailTemplate::findOrFail($validated['template_id']);
        $clients  = Client::whereIn('id', $validated['client_ids'])->get();

        foreach ($clients as $client) {
            try {
                $body = str_replace(
                    ['{{client_name}}', '{{date}}'],
                    [$client->name, now()->format('Y-m-d')],
                    $template->body
                );

                $this->sendEmailDirect(
                    $client,
                    $client->email,
                    $template->subject,
                    $body
                );
            } catch (\Throwable $e) {
                Log::error("Bulk email failed for {$client->email}: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Bulk emails dispatched']);
    }

    /**
     * Helper to send email directly without validation or recursion
     */
    protected function sendEmailDirect(Client $client, string $to, string $subject, string $body)
    {
        $credentialService = new DynamicChannelCredentialService($client, 'email');
        $credentials = $credentialService->getCredential();

        config([
            'mail.mailers.dynamic' => [
                'transport'  => 'smtp',
                'host'       => $credentials->host,
                'port'       => $credentials->port,
                'encryption' => $credentials->encryption,
                'username'   => $credentials->username,
                'password'   => $credentials->password,
            ],
            'mail.from.address' => $credentials->from_address,
            'mail.from.name'    => $credentials->from_name,
        ]);

        Mail::mailer('dynamic')->to($to)->send(new GenericEmail($subject, $body));

        Email::create([
            'to'        => $to,
            'subject'   => $subject,
            'body'      => $body,
            'client_id' => $client->id,
            'status'    => 'sent'
        ]);
    }
}
