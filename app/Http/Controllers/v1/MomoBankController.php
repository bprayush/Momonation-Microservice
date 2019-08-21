<?php

namespace App\Http\Controllers\v1;
use Auth;
use App\User;
use App\Models\Setting;
use App\Models\Momobank;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\HelperClasses\BankHelper;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\ServiceAccount;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Messaging\CloudMessage;

class MomoBankController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Factory $firebase)
    {
        // dd($firebase);
    }

    public function test(){
        $serviceAccount = ServiceAccount::fromJson(Storage::disk('public')->get('firebaseKarkhanaService.json'));
        $firebase = (new Factory)
                ->withServiceAccount($serviceAccount)
                // The following line is optional if the project id in your credentials file
                // is identical to the subdomain of your Firebase project. If you need it,
                // make sure to replace the URL with the URL of your project.
                ->create();

        $messaging = $firebase->getMessaging();
        $topic = 'a-topic';
        $message = CloudMessage::withTarget('topic', $topic);
        // dd($message);
        $messaging->send($message);
        dd($message);
        // dd(Storage::disk('public')->get('BeeCreativeManagementLocal.json'));
        dd($serviceAccount);
        dd('asd');
    }

    public function transfer(Request $request) {
        $this->validate(
            $request,
            [
                'receiver' => 'required',
                'amount' => 'required',
                'title' => 'required',
                'description' => 'required',
            ]
        );

        $amount = $request->input('amount');

        $authUser = Auth::User();
        
        $receiver = User::find($request->input('receiver'));
        if ($receiver == null) 
            return response()->json('Receiver does not exist in our database', 512);

        if ($receiver->id == $authUser->id)
            return response()->json('You can\'t do that, that\'s cheating!', 512);

        $authBank = null;
        $receiverBank = null;

        if ($authUser->bank == null) {
            $authBank = BankHelper::createBankAccount($authUser);
        } else {
            $authBank = $authUser->bank;
        }

        if ($authBank == null)
            return response()->json('Could not find user bank account.', 500);

        if ($authBank->raw < $amount)
            return response()->json('You do not have enough raw Mo:Mo', 406);

        if ($receiver->bank == null) {
            $receiverBank = BankHelper::createBankAccount($receiver);
        } else {
            $receiverBank = $receiver->bank;
        }

        if ($receiverBank == null)
            return response()->json('Could not find receiver bank account.', 500);

        if (BankHelper::checkTransferLimit($amount) == false) {
            return response()->json('Transaction limit is '.
                Setting::first()->momo_transfer_limit.
                ' mo:mo at a time.', 500);
        }

        if (BankHelper::checkDailyLimit($receiver) == false) {
            return response()->json('Daily limit for transactions reached.', 500);
        }
        
        DB::connection('momonation')->beginTransaction();
        try {
            if($authBank->raw < $amount)
                return response()->json('You do not have enough raw Mo:Mo', 406);
            $receiverBank->cooked = $receiverBank->cooked + $amount;
            $authBank->raw = $authBank->raw - $amount;
            $receiverBank->save();
            $authBank->save();
            $transaction = Transaction::create([
                'sender' => $authUser->id,
                'receiver' => $receiver->id,
                'amount' => $amount,
                'by_user' => true,
                'cooked' => true,
            ]);
            $feed = $transaction->feed()->create([
                'sender' => $authUser->id,
                'receiver' => $receiver->id,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
            ]);

            $receiver->notify(new UserNotification(
                [
                    'feed_id' => $feed->id
                ]
            ));

            DB::connection('momonation')->commit();
        } catch (\Exception $e) {
            DB::connection('momonation')->rollback();
            \Log::error($e);
            // dd($e);
            return response()->json('Could not deliver the Mo:Mo', 500);
        }
        
        return response()->json('Successfully delivered ' . $amount . ' cooked Mo:Mo');
    }
}
