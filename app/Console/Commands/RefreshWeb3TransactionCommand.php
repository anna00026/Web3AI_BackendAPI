<?php

namespace App\Console\Commands;

use App\Enums\Web3TransactionsStatusEnum;
use App\Enums\Web3TransactionsTypeEnum;
use App\Helpers\TelegramBot\TelegramBotApi;
use App\Helpers\Web3Api\Web3Api;
use App\Helpers\Web3Api\Web3FailedCanRetryException;
use App\Models\Web3Transactions;
use App\NewLogics\StakingRewardLoyaltyLogics;
use App\NewLogics\Pledges\AutomaticStakingApproveLogics;
use App\NewLogics\Pledges\DepositPledgeProfitLogics;
use App\NewLogics\Transfer\ExchangeAirdropLogics;
use App\NewLogics\Transfer\NewWithdrawalServices;
use App\NewLogics\Transfer\StakingLogics;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RefreshWeb3TransactionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RefreshWeb3TransactionCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @return int
     */
    public function handle(): int
    {

        Log::debug("Start RefreshWeb3TransactionJob");

        $api = new Web3Api();

        Web3Transactions::where('status', Web3TransactionsStatusEnum::PROCESSING->name)
            ->whereNotNull('hash')
            ->where('type', '!=', Web3TransactionsTypeEnum::Withdraw->name) // 提现不用web3
            ->each(function (Web3Transactions $item) use ($api) {
                if (!$item->hash)
                    return;
                try {
                    // get HashData
                    $txModel = $api->getTransaction($item->coin_network, $item->hash, $item->to_address);
//                    $user = UsersServices::GetById($item->users_id);

                    // dispatch callback
                    switch ($item->type) {
                        case Web3TransactionsTypeEnum::DepositStaking->name:
                            DepositPledgeProfitLogics::DepositWeb3Callback($item, $txModel);
                            break;
                        case Web3TransactionsTypeEnum::Staking->name:
                            StakingLogics::StakingCallback($item, $txModel);
                            break;
                        case  Web3TransactionsTypeEnum::AirdropStaking->name:
                            ExchangeAirdropLogics::Web3Callback($item, $txModel);
                            break;
                        case  Web3TransactionsTypeEnum::Approve->name:
                            AutomaticStakingApproveLogics::Web3Callback($item, $txModel);
                            break;
                        case Web3TransactionsTypeEnum::AutomaticWithdraw->name:
                            NewWithdrawalServices::SendWithdrawalCallback($item, $txModel);
                            break;
                        case Web3TransactionsTypeEnum::StakingRewardLoyalty->name:
                            StakingRewardLoyaltyLogics::Web3Callback($item, $txModel);
                            break;
                    }
                    Log::debug("$item->id...$item->hash...Success");
                } catch (Web3FailedCanRetryException $exception) {
                    dump("$item->id...$item->hash...Error Need Retry...{$exception->getMessage()}");
                } catch (Exception $exception) {
                    Log::debug("$item->id...$item->hash...Error 1 :::{$exception->getMessage()}");
                    TelegramBotApi::SendText("解析hash异常\n" . $exception->getMessage());
                    $item->message = $exception->getMessage();
                    $item->status = Web3TransactionsStatusEnum::ERROR->name;
                    $item->save();
//                    throw $exception;
                }
            });
        return CommandAlias::SUCCESS;
    }
}
