/*!
 * LOPANGO QR Code Generator
 * Based on qrcode-generator (c) 2009 Kazuhiko Arase - MIT License
 */
var LopangoQR=(function(){
var a=[];var b=[];(function(){for(var c=0;c<8;c++)a[c]=1<<c;for(var c=8;c<256;c++)a[c]=a[c-4]^a[c-5]^a[c-6]^a[c-8];for(var c=0;c<255;c++)b[a[c]]=c})();
function d(e,f){if(e==0||f==0)return 0;return a[(b[e]+b[f])%255]}
function g(h,i){this.num=h;this.shift=i||0}
g.prototype={get:function(j){return this.num[j+this.shift]},len:function(){return this.num.length-this.shift},mul:function(e){var n=Array(this.len()+e.len()-1);for(var i=0;i<n.length;i++)n[i]=0;for(var i=0;i<this.len();i++)for(var j=0;j<e.len();j++)n[i+j]^=d(this.get(i),e.get(j));return new g(n)},mod:function(e){if(this.len()-e.len()<0)return this;var r=b[this.get(0)]-b[e.get(0)];var n=Array(this.num.length);for(var i=0;i<this.num.length;i++)n[i]=this.num[i];for(var i=0;i<e.len();i++)n[i]^=d(e.get(i),a[(r+256)%255]);return new g(n,1).mod(e)}};
function k(l){var p=new g([1]);for(var i=0;i<l;i++)p=p.mul(new g([1,a[i]]));return p}
var PP=[[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38]];
function QR(v,e){this.v=v;this.e=e;this.mod=null;this.mc=0;this.dc=null;this.dl=[]}
QR.prototype={addData:function(s){this.dl.push(s);this.dc=null},isDark:function(r,c){return this.mod[r][c]},getMC:function(){return this.mc},make:function(){this.mi(false,this.bmp())},mi:function(t,mp){this.mc=this.v*4+17;this.mod=[];for(var r=0;r<this.mc;r++){this.mod[r]=[];for(var c=0;c<this.mc;c++)this.mod[r][c]=null}this.pp(0,0);this.pp(this.mc-7,0);this.pp(0,this.mc-7);this.ap();this.tp();this.ti(t,mp);if(this.dc==null)this.dc=QR.cd(this.v,this.e,this.dl);this.md(this.dc,mp)},pp:function(r,c){for(var dr=-1;dr<=7;dr++)for(var dc=-1;dc<=7;dc++){if(r+dr<0||this.mc<=r+dr||c+dc<0||this.mc<=c+dc)continue;this.mod[r+dr][c+dc]=(0<=dr&&dr<=6&&(dc==0||dc==6))||(0<=dc&&dc<=6&&(dr==0||dr==6))||(2<=dr&&dr<=4&&2<=dc&&dc<=4)}},bmp:function(){var ml=0,mp=0;for(var i=0;i<8;i++){this.mi(true,i);var l=this.lp();if(i==0||ml>l){ml=l;mp=i}}return mp},lp:function(){var mc=this.mc,lp=0;for(var r=0;r<mc;r++)for(var c=0;c<mc;c++){var s=0,dk=this.isDark(r,c);for(var dr=-1;dr<=1;dr++)for(var dc=-1;dc<=1;dc++){if(dr==0&&dc==0)continue;try{if(dk==this.isDark(r+dr,c+dc))s++}catch(e){}}if(s>5)lp+=3+s-5}for(var r=0;r<mc-1;r++)for(var c=0;c<mc-1;c++){var n=0;if(this.isDark(r,c))n++;if(this.isDark(r+1,c))n++;if(this.isDark(r,c+1))n++;if(this.isDark(r+1,c+1))n++;if(n==0||n==4)lp+=3}for(var r=0;r<mc;r++)for(var c=0;c<mc-6;c++){if(this.isDark(r,c)&&!this.isDark(r,c+1)&&this.isDark(r,c+2)&&this.isDark(r,c+3)&&this.isDark(r,c+4)&&!this.isDark(r,c+5)&&this.isDark(r,c+6))lp+=40}var dk=0;for(var c=0;c<mc;c++)for(var r=0;r<mc;r++)if(this.isDark(r,c))dk++;lp+=Math.abs(100*dk/mc/mc-50)/5*10;return lp},ap:function(){var pos=PP[this.v];for(var i=0;i<pos.length;i++)for(var j=0;j<pos.length;j++){var r=pos[i],c=pos[j];if(this.mod[r][c]!=null)continue;for(var dr=-2;dr<=2;dr++)for(var dc=-2;dc<=2;dc++)this.mod[r+dr][c+dc]=dr==-2||dr==2||dc==-2||dc==2||(dr==0&&dc==0)}},tp:function(){for(var r=8;r<this.mc-8;r++)if(this.mod[r][6]==null)this.mod[r][6]=r%2==0;for(var c=8;c<this.mc-8;c++)if(this.mod[6][c]==null)this.mod[6][c]=c%2==0},ti:function(t,mp){var dt=(1<<3)|mp,bits=bti(dt);var p1=[[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];for(var i=0;i<15;i++)this.mod[p1[i][0]][p1[i][1]]=!t&&((bits>>i)&1)==1;var p2=[[this.mc-1,8],[this.mc-2,8],[this.mc-3,8],[this.mc-4,8],[this.mc-5,8],[this.mc-6,8],[this.mc-7,8],[8,this.mc-8],[8,this.mc-7],[8,this.mc-6],[8,this.mc-5],[8,this.mc-4],[8,this.mc-3],[8,this.mc-2],[8,this.mc-1]];for(var i=0;i<15;i++)this.mod[p2[i][0]][p2[i][1]]=!t&&((bits>>i)&1)==1;this.mod[this.mc-8][8]=!t},md:function(dt,mp){var inc=-1,row=this.mc-1,bi=7,by=0;for(var col=this.mc-1;col>0;col-=2){if(col==6)col--;while(true){for(var c=0;c<2;c++){if(this.mod[row][col-c]==null){var dk=false;if(by<dt.length)dk=((dt[by]>>>bi)&1)==1;if(QR.gm(mp,row,col-c))dk=!dk;this.mod[row][col-c]=dk;bi--;if(bi==-1){by++;bi=7}}}row+=inc;if(row<0||this.mc<=row){row-=inc;inc=-inc;break}}}}};
QR.gm=function(mp,i,j){switch(mp){case 0:return(i+j)%2==0;case 1:return i%2==0;case 2:return j%3==0;case 3:return(i+j)%3==0;case 4:return(~~(i/2)+~~(j/3))%2==0;case 5:return(i*j)%2+(i*j)%3==0;case 6:return((i*j)%2+(i*j)%3)%2==0;case 7:return((i*j)%3+(i+j)%2)%2==0}return false};
// RS blocks: [count, totalCodewords, dataCodewords] for ECC Level M
var RST=[[],[1,26,16],[1,44,28],[1,70,44],[2,50,32],[2,67,43],[4,43,27]];
QR.cd=function(v,e,dl){var rs=RST[v];var blocks=[{tc:rs[1],dc:rs[2]}];
// encode bytes
var buf=[];var bytes=[];for(var i=0;i<dl.length;i++){var s=dl[i];for(var j=0;j<s.length;j++){var c=s.charCodeAt(j);if(c<128)bytes.push(c);else if(c<2048){bytes.push(192|(c>>6));bytes.push(128|(c&63))}else{bytes.push(224|(c>>12));bytes.push(128|((c>>6)&63));bytes.push(128|(c&63))}}}
// mode + length + data
buf.push(0);buf.push(1);buf.push(0);buf.push(0); // 0100 = byte mode
var len=bytes.length;for(var i=7;i>=0;i--)buf.push((len>>i)&1);
for(var i=0;i<bytes.length;i++)for(var j=7;j>=0;j--)buf.push((bytes[i]>>j)&1);
var tdc=0;for(var i=0;i<blocks.length;i++)tdc+=blocks[i].dc;
for(var i=0;i<4&&buf.length<tdc*8;i++)buf.push(0);
while(buf.length%8!=0)buf.push(0);
while(buf.length<tdc*8){for(var i=7;i>=0;i--)buf.push((0xEC>>i)&1);if(buf.length<tdc*8)for(var i=7;i>=0;i--)buf.push((0x11>>i)&1)}
// to bytes
var data=[];for(var i=0;i<buf.length;i+=8){var byt=0;for(var j=0;j<8;j++)byt=(byt<<1)|(buf[i+j]||0);data.push(byt)}
// RS error correction
var off=0,mdc=0,mec=0,dcd=[],ecd=[];
for(var r=0;r<blocks.length;r++){var dc=blocks[r].dc,ec=blocks[r].tc-dc;mdc=Math.max(mdc,dc);mec=Math.max(mec,ec);dcd[r]=data.slice(off,off+dc);off+=dc;var rsp=k(ec),raw=new g(dcd[r],rsp.len()-1),mp2=raw.mod(rsp);ecd[r]=[];for(var i=0;i<rsp.len()-1;i++){var mi=i+mp2.len()-(rsp.len()-1);ecd[r].push(mi>=0?mp2.get(mi):0)}}
var tc=0;for(var i=0;i<blocks.length;i++)tc+=blocks[i].tc;
var res=Array(tc),idx=0;
for(var i=0;i<mdc;i++)for(var r=0;r<blocks.length;r++)if(i<dcd[r].length)res[idx++]=dcd[r][i];
for(var i=0;i<mec;i++)for(var r=0;r<blocks.length;r++)if(i<ecd[r].length)res[idx++]=ecd[r][i];
return res};
function bti(dt){var d=dt<<10;while(bd(d)-bd(1335)>=0)d^=1335<<(bd(d)-bd(1335));return(dt<<10)|d^21522}
function bd(dt){var d=0;while(dt!=0){d++;dt>>>=1}return d}
function gv(s){var l=0;for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c<128)l+=1;else if(c<2048)l+=2;else l+=3}
if(l<=16)return 1;if(l<=28)return 2;if(l<=44)return 3;if(l<=64)return 4;if(l<=86)return 5;return 6}

function draw(canvasId,data,size){
  size=size||120;
  var canvas=document.getElementById(canvasId);
  if(!canvas)return;
  var ex=document.getElementById(canvasId+'_qr');
  if(ex)ex.remove();
  if(!data||data.indexOf('XXXX')>=0||(data.match(/-0{3}-/))){
    canvas.style.display='block';canvas.width=size;canvas.height=size;
    var ctx=canvas.getContext('2d');ctx.fillStyle='#f0f6f0';ctx.fillRect(0,0,size,size);
    ctx.strokeStyle='#c8d8c8';ctx.strokeRect(2,2,size-4,size-4);
    ctx.fillStyle='#6a8a6a';ctx.font='bold '+Math.floor(size/8)+'px monospace';ctx.textAlign='center';ctx.fillText('QR',size/2,size/2+4);return;
  }
  try{
    var v=gv(data);
    var qr=new QR(v,1);qr.addData(data);qr.make();
    var mc=qr.getMC();
    var cs=Math.floor((size-16)/mc);if(cs<2)cs=2;
    var mg=Math.floor((size-mc*cs)/2);
    var ts=mc*cs+mg*2;
    var tc=document.createElement('canvas');tc.width=ts;tc.height=ts;
    var ctx=tc.getContext('2d');
    ctx.fillStyle='#ffffff';ctx.fillRect(0,0,ts,ts);
    ctx.fillStyle='#0f4c35';
    for(var r=0;r<mc;r++)for(var c=0;c<mc;c++)if(qr.isDark(r,c))ctx.fillRect(mg+c*cs,mg+r*cs,cs,cs);
    var img=document.createElement('img');img.id=canvasId+'_qr';img.width=size;img.height=size;
    img.src=tc.toDataURL('image/png');img.style.cssText='display:block;border-radius:4px;';
    canvas.style.display='none';
    canvas.parentNode.insertBefore(img,canvas.nextSibling);
  }catch(err){
    canvas.style.display='block';canvas.width=size;canvas.height=size;
    var ctx=canvas.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,size,size);
    ctx.strokeStyle='#0f4c35';ctx.lineWidth=2;ctx.strokeRect(2,2,size-4,size-4);
    ctx.fillStyle='#0f4c35';ctx.font=Math.floor(size/14)+'px monospace';ctx.textAlign='center';
    ctx.fillText(data.substring(0,16),size/2,size/2);
    console.error('QR error:',err);
  }
}
function toDataURL(data,size){return''}
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('canvas[data-qr]').forEach(function(c){var d=c.dataset.qr,s=parseInt(c.dataset.qrSize||'96');if(d)draw(c.id,d,s)})});
return{draw:draw,toDataURL:toDataURL};
})();
