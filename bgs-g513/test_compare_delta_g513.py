import importlib.util
import hashlib
import json
from pathlib import Path
import tempfile
import unittest

s = importlib.util.spec_from_file_location('delta', Path(__file__).with_name('compare-delta-g513.py'))
m = importlib.util.module_from_spec(s)
s.loader.exec_module(m)

class TestDelta(unittest.TestCase):
    def test_create_modify_and_missing(self):
        old = {'customers': {'1': {'id': 1,'name':'A'}, '2': {'id': 2,'name':'B'}, '3':{'id':3,'name':'C'}}}
        new = {'customers': {'1': {'name':'A','id':1}, '2': {'id':2,'name':'B2'},'4': {'id':4,'name':'D'}}}
        a,t,d = m.compare(old,new)
        self.assertEqual(t, {'created':1,'updated':1,'missing_from_new_snapshot':1,'unchanged':1})
        self.assertEqual(d['tables']['customers']['missing_source_ids'], ['3'])
        self.assertEqual(d['tables']['customers']['updated'][0]['after']['name'], 'B2')
    def test_same_snapshots_do_not_change(self):
        old={'products':{'1':{'id':1,'name':'A'}}}
        a,t,d=m.compare(old,old)
        self.assertEqual(t['updated'],0)
        self.assertEqual(t['created'],0)

if __name__ == '__main__':
    unittest.main(verbosity=2)
