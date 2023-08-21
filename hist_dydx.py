# nav to dir
# run with: python3.9 hist_dydx.py
# https://dydxprotocol.github.io/v3-teacher/

import os
from dydx3 import Client
from dydx3.helpers.request_helpers import json_stringify
from web3 import Web3
import json
from datetime import date
from datetime import datetime
from time import sleep

def goto(linenum):
    global line
    line = linenum

with open('config_dydx.json') as f:
    config = json.load(f)

# append result or rebuild
append = True

# set arrays and query dates
last_date = datetime(2000,1,1).isoformat() + "Z" # this will be the time of the last complete record
res = {
'txn_hist': {'data': [], 'file': 'db/dydx_txn_hist.json', 'last_date': last_date, 'last_key': 'createdAt'}
, 'pay_hist': {'data': [], 'file': 'db/dydx_pay_hist.json', 'last_date': last_date, 'last_key': 'effectiveAt'}
, 'tran_hist': {'data': [], 'file': 'db/dydx_tran_hist.json', 'last_date': last_date, 'last_key': 'createdAt'}
, 'positions': {'data': [], 'file': 'db/dydx_positions.json', 'last_date': last_date, 'last_key': 'createdAt'}
, 'acc_balance': {'data': [], 'file': 'db/dydx_acc_status.json', 'last_date': last_date, 'last_key': ''}
}

# set up data if appending
for r in res:
    last_key = res[r]['last_key']
    if append and last_key != '' and os.path.getsize(res[r]['file']) > 0:
        with open(res[r]['file']) as f:
            res[r]['data'] = json.load(f)
        last_record = max(res[r]['data'], key=lambda x: datetime.fromisoformat(x[last_key][:-1]))
        res[r]['last_date'] = last_record[last_key]
        # exceptions for positions data to handle open positions
        if r == 'positions':
            last_date = datetime.fromisoformat(res[r]['last_date'][:-1])
            # get earliest date of open position
            for record in res[r]['data']:
                record_date = datetime.fromisoformat(record[last_key][:-1])
                if record['status'] == 'OPEN' and record_date < last_date:
                    last_date = record_date
            # delete if later last_date (open position date or if none open will pass through)
            reduced_data = []
            for record in res[r]['data']:
                record_date = datetime.fromisoformat(record[last_key][:-1])
                if record['status'] != 'OPEN' and record_date < last_date:
                    reduced_data.append(record)
            # store and repeat
            res[r]['data'] = reduced_data
            last_record = max(res[r]['data'], key=lambda x: datetime.fromisoformat(x[last_key][:-1]))
            res[r]['last_date'] = last_record[last_key]
print('set up arrays, append data =', append, '... querying api for new data')

# iterate accounts
for acc in config['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    # iterate queries
    for r in res:
        last_key = res[r]['last_key']
        last_date = datetime.fromisoformat(res[r]['last_date'][:-1])
        date_limit = f'{datetime.utcnow().isoformat()[:-3]}Z'
        query_remains = True
        while query_remains:
            sleep(0.07)
            i = 0
            if r == 'txn_hist':
                response = private_client.private.get_fills(
                    created_before_or_at = date_limit, limit = 100
                )
                data = response.data['fills']
            if r == 'pay_hist':
                response = private_client.private.get_funding_payments(
                    effective_before_or_at = date_limit, limit = 100
                )
                data = response.data['fundingPayments']
            if r == 'tran_hist':
                response = private_client.private.get_transfers(
                    created_before_or_at = date_limit, limit = 100
                )
                data = response.data['transfers']
            if r == 'positions':
                response = private_client.private.get_positions(
                    created_before_or_at = date_limit, limit = 100
                )
                data = response.data['positions']
            if r == 'acc_balance':
                response = private_client.private.get_account(
                    ethereum_address = acc['ethereumAddress']
                )
                res[r]['data'].append(
                    [acc['ethereumAddress'], response.data['account']['freeCollateral']]
                )
                query_remains = False
            else: # if not acc_balance then iterate
                for record in data:
                    record_date = datetime.fromisoformat(record[last_key][:-1])
                    if record_date > last_date:
                        record['wallet'] = acc['ethereumAddress']
                        res[r]['data'].append(record)
                        i += 1
                if i < 100:
                    query_remains = False
                else: date_limit = record[last_key]

# iterate saving
for r in res:
    # deduplicate the data
    dedupe = []
    for record in res[r]['data']:
        exist = False
        for e in dedupe:
            if record == e:
                exist = True
                break
        if not exist:
            dedupe.append(record)
    n_dupe = len(res[r]['data']) - len(dedupe)
    print('saved', len(dedupe), r, 'records, deduped:', n_dupe)
    # save
    with open(res[r]['file'], 'w') as f:
        json.dump(dedupe, f, indent = 0)
