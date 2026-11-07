const fs=require('fs'),path=require('path');
const parser=require('/home/user/DeVco/node_modules/php-parser');
const engine=new parser({parser:{extractDoc:false,php7:true},ast:{withPositions:false}});
let files=[];
(function walk(d){for(const e of fs.readdirSync(d,{withFileTypes:true})){if(e.name==='node_modules'||e.name==='.git')continue;const p=path.join(d,e.name);if(e.isDirectory())walk(p);else if(e.name.endsWith('.php'))files.push(p);}})('.');
let bad=0;
for(const f of files){const code=fs.readFileSync(f,'utf8');try{engine.parseCode(code,f);}catch(err){bad++;console.log('FAIL',f,'line',err.lineNumber||'?','-',String(err.message).slice(0,300));}}
console.log(`checked ${files.length} files, ${bad} with syntax errors`);
