const fs=require('fs'),path=require('path');
const csstree=require('/home/user/DeVco/node_modules/css-tree');
let files=[];
(function walk(d){for(const e of fs.readdirSync(d,{withFileTypes:true})){if(['node_modules','.git'].includes(e.name))continue;const p=path.join(d,e.name);if(e.isDirectory())walk(p);else if(e.name.endsWith('.css'))files.push(p);}})('/home/user/DeVco/assets');
let bad=0;
for(const f of files){const code=fs.readFileSync(f,'utf8');try{csstree.parse(code,{positions:true});}catch(err){bad++;console.log('FAIL',f,'line',err.line,'-',String(err.message).slice(0,200));}}
console.log(`checked ${files.length} css files, ${bad} with errors`);
