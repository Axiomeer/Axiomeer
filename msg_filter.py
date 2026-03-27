import sys, re
msg = sys.stdin.read()
msg = re.sub(r'(?m)^Stage \d+: ', '', msg)
msg = msg.replace('Deploy real Semantic Kernel', 'Deploy Semantic Kernel')
sys.stdout.write(msg)
