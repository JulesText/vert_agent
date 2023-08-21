# nav to dir
# run with: python3.9 hist_dydx.py
# https://dydxprotocol.github.io/v3-teacher/

from dydx3 import Client
from dydx3.helpers.request_helpers import json_stringify
from web3 import Web3
import json
# import datetime
from datetime import date
from datetime import datetime
from time import sleep

def goto(linenum):
    global line
    line = linenum

with open('config_dydx.json') as f:
    data = json.load(f)

# append result or rebuild
append = True

# set arrays and query dates
if append:
    with open('db/dydx_txn_hist.json') as f:
        txn_hist = json.load(f)
    last_date = max(txn_hist, key=lambda x: datetime.fromisoformat(x['createdAt'][:-1]))
    txn_last_date = datetime.fromisoformat(last_date['createdAt'][:-1])
    with open('db/dydx_pay_hist.json') as f:
        pay_hist = json.load(f)
    last_date = max(pay_hist, key=lambda x: datetime.fromisoformat(x['effectiveAt'][:-1]))
    pay_last_date = datetime.fromisoformat(last_date['effectiveAt'][:-1])
    with open('db/dydx_tran_hist.json') as f:
        tran_hist = json.load(f)
    last_date = max(tran_hist, key=lambda x: datetime.fromisoformat(x['createdAt'][:-1]))
    tran_last_date = datetime.fromisoformat(last_date['createdAt'][:-1])
    last_date = max(txn_last_date, pay_last_date, tran_last_date)
else:
    txn_hist = []
    pay_hist = []
    tran_hist = []
    last_date = datetime(2000,1,1)

# transaction history
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    date_limit = f'{datetime.utcnow().isoformat()[:-3]}Z'
    query_remains = True
    while query_remains:
        i = 0
        response = private_client.private.get_fills(
            created_before_or_at = date_limit,
            limit = 100
        )
        sleep(0.07)
        for record in response.data['fills']:
            record_date = datetime.fromisoformat(record['createdAt'][:-1])
            if record_date > last_date:
                record['wallet'] = acc['ethereumAddress']
                txn_hist.append(record)
                i += 1
        if i < 100:
            query_remains = False
        else: date_limit = record['createdAt']
with open('db/dydx_txn_hist.json', 'w') as f:
    json.dump(txn_hist, f, indent=2)

# payment history
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    date_limit = f'{datetime.utcnow().isoformat()[:-3]}Z'
    query_remains = True
    while query_remains:
        i = 0
        response = private_client.private.get_funding_payments(
            effective_before_or_at = date_limit,
            limit = 100
        )
        sleep(0.07)
        for record in response.data['fundingPayments']:
            record_date = datetime.fromisoformat(record['effectiveAt'][:-1])
            if record_date > last_date:
                record['wallet'] = acc['ethereumAddress']
                pay_hist.append(record)
                i += 1
        if i < 100:
            query_remains = False
        else: date_limit = record['effectiveAt']
with open('db/dydx_pay_hist.json', 'w') as f:
    json.dump(pay_hist, f, indent=2)

# transfer history
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    response = private_client.private.get_transfers()
    sleep(0.07)
    for record in response.data['transfers']:
        record['wallet'] = acc['ethereumAddress']
        tran_hist.append(record)
with open('db/dydx_tran_hist.json', 'w') as f:
    json.dump(tran_hist, f, indent=2)

# account balances
acc_balance = []
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    response = private_client.private.get_account(
        ethereum_address = acc['ethereumAddress']
    )
    sleep(0.07)
    acc_balance.append(
        [acc['ethereumAddress'], response.data['account']['freeCollateral']]
        )
with open('db/dydx_acc_status.json', 'w') as f:
    json.dump(acc_balance, f, indent=2)

# account open positions
positions = []
for acc in data['accounts']:
    private_client = Client(
        host = 'https://api.dydx.exchange',
        api_key_credentials = {
            'key': acc['api_key'],
            'secret': acc['api_secret'],
            'passphrase': acc['api_passphrase']
        }
    )
    response = private_client.private.get_positions()
    sleep(0.07)
    for record in response.data['positions']:
        record['wallet'] = acc['ethereumAddress']
        positions.append(record)
with open('db/dydx_positions.json', 'w') as f:
    json.dump(positions, f, indent=2)
