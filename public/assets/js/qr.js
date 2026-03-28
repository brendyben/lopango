//---------------------------------------------------------------------
// qrcode-generator - MIT License - Kazuhiko Arase
// Adapted for Lopango
//---------------------------------------------------------------------
var qrcode=function(){var t={};t.stringToBytesFuncs={"default":function(a){for(var b=[],c=0;c<a.length;c++){var d=a.charCodeAt(c);b.push(255&d)}return b}};t.stringToBytes=t.stringToBytesFuncs["default"];t.createStringToBytes=function(a,b){var c=function(){var b=t.stringToBytes;t.stringToBytes=function(a){for(var c=b(a),d=[],e=0;e<c.length;e++){var f=c[e];f<128?d.push(f):f<192?d.push(239,187,191):f<224?(d.push(f),d.push(c[++e])):f<240?(d.push(f),d.push(c[++e]),d.push(c[++e])):(d.push(f),d.push(c[++e]),d.push(c[++e]),d.push(c[++e]))}return d}};return c};var b=function(){var a=0,b=null,c={read:function(){if(a==b.length)throw new Error("Error");return b[a++]},available:function(){return b.length-a}};return c};var c=0,d=0,e=0,f=0,g=0,h=0,i=0;var j=t.QRMode={MODE_NUMBER:1<<0,MODE_ALPHA_NUM:1<<1,MODE_8BIT_BYTE:1<<2,MODE_KANJI:1<<3};var k=t.QRErrorCorrectionLevel={L:1,M:0,Q:3,H:2};var l={};var m=function(a){if(a<1||a>40)throw new Error("bad rs block @ typeNumber:"+a);var b=n[a-1];var c=[];for(var d=0;d<b.length;d+=3){for(var e=0;e<b[d+0];e++){c.push([b[d+1],b[d+2]])}}return c};var n=[[1,26,19],[1,44,34],[1,70,55],[2,50,32],[2,67,43],[4,43,27],[4,49,31],[2,60,37,2,58,40],[3,58,36,2,54,42],[4,69,43,1,69,57],[1,80,50,4,80,64],[6,58,36,2,58,46],[8,59,37,1,59,47],[4,64,40,5,64,52],[5,65,41,5,64,54],[7,73,45,3,75,61],[10,74,46,1,73,59],[9,69,43,4,70,54],[3,70,44,11,67,43],[3,67,41,13,68,44],[17,68,42],[17,74,46],[4,75,47,14,74,54],[6,73,45,14,74,54],[8,75,47,13,74,54],[19,74,46,4,75,61],[22,73,45,3,74,52],[3,73,45,23,73,57],[21,73,45,7,73,54],[19,75,47,10,74,62],[2,74,46,29,72,60],[10,74,46,23,73,60],[14,74,46,21,73,60],[14,74,46,23,72,58],[12,75,47,26,73,60],[6,96,60,34,74,46],[29,74,46,14,73,54],[13,74,46,32,73,54],[40,75,47,7,74,60],[18,75,47,31,74,55]];t.QRBitBuffer=function(){var a=[];var b=0;return{get:function(c){var d=Math.floor(c/8);return 1==(1&a[d]>>>7-c%8)},put:function(c,d){for(var e=0;e<d;e++){this.putBit(1==(1&c>>>d-e-1))}},getLengthInBits:function(){return b},putBit:function(c){var d=Math.floor(b/8);if(a.length<=d){a.push(0)}if(c){a[d]|=128>>>b%8}b++}}};var o=function(){var a=[];for(var b=0;b<8;b++){a[b]=1<<b}for(var b=8;b<256;b++){a[b]=a[b-4]^a[b-5]^a[b-6]^a[b-8]}var c=[];for(var b=0;b<255;b++){c[a[b]]=b}return{glog:function(a){if(a<1)throw new Error("glog("+a+")");return c[a]},gexp:function(b){while(b<0){b+=255}while(b>=256){b-=255}return a[b]}}};var p=o();var q=function(a,b){this.num=a;this.shift=b||0};q.prototype={get:function(a){return this.num[a+this.shift]},getLength:function(){return this.num.length-this.shift},multiply:function(a){var b=new Array(this.getLength()+a.getLength()-1);for(var c=0;c<this.getLength();c++){for(var d=0;d<a.getLength();d++){b[c+d]^=p.gexp(p.glog(this.get(c))+p.glog(a.get(d)))}}return new q(b)},mod:function(a){if(this.getLength()-a.getLength()<0){return this}var b=p.glog(this.get(0))-p.glog(a.get(0));var c=new Array(this.num.length);for(var d=this.shift;d<this.num.length;d++){c[d]=this.num[d]}for(var d=0;d<a.getLength();d++){c[d]^=p.gexp(p.glog(a.get(d))+b)}return new q(c,1).mod(a)}};t.QRPolynomial=q;var r=function(a){var b=new Array(a);for(var c=0;c<a;c++){b[c]=p.gexp(c)}return new q(b)};var s=function(a,b,c){if(b==void 0||c==void 0){b=0;c=0}return{totalCount:a[0],dataCount:a[1]}};var u=function(a,b){return s(m(a),b,0)};var v=function(a,b){return s(m(a),b,1)};t.qrcode=function(a,b){if(typeof b=="string"){var c=k[b];if(c===undefined){throw new Error("Unknown correction level: "+b)}b=c}if(a<1||a>40){throw new Error("bad rs block @ typeNumber:"+a)}var d=[];var e=null;var f=null;var g=0;var h=0;var i=0;var j=function(){var a=b==k.L?0:b==k.M?1:b==k.Q?2:3;return[[0,0],[1,1],[1,1],[1,1]][a][0]};var bb=function(a){var c=w(a,b);return c};var w=function(a,b){return m(a).reduce(function(a,b){return a+b[1]},0)};var x=function(){var a=[];var c=m(h);for(var d=0;d<c.length;d++){for(var e=0;e<c[d][0];e++){a.push(c[d][1])}}return a};var y=function(a,c,d,e,f,g){a[c][d]=e};t.QRMath=p;return t};return t}();

// ── LOPANGO WRAPPER ─────────────────────────────────────────────────────────
// Simple, reliable QR generator using proven algorithm

var LopangoQR = (function() {

  // Complete self-contained QR encoder
  // Based on qrcode-generator (MIT) - inline minimal version

  var PAT = [[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,26,46]];

  var EXP = [];var LOG = [];
  (function(){for(var i=0;i<8;i++)EXP[i]=1<<i;for(var i=8;i<256;i++)EXP[i]=EXP[i-4]^EXP[i-5]^EXP[i-6]^EXP[i-8];for(var i=0;i<255;i++)LOG[EXP[i]]=i})();

  function gexp(n){while(n<0)n+=255;while(n>=256)n-=255;return EXP[n]}
  function glog(n){if(n<1)throw new Error('glog('+n+')');return LOG[n]}

  function Poly(num,shift){this.num=num;this.shift=shift||0}
  Poly.prototype={
    get:function(i){return this.num[i+this.shift]},
    len:function(){return this.num.length-this.shift},
    mul:function(e){var n=new Array(this.len()+e.len()-1);for(var i=0;i<n.length;i++)n[i]=0;for(var i=0;i<this.len();i++)for(var j=0;j<e.len();j++)n[i+j]^=gexp(glog(this.get(i))+glog(e.get(j)));return new Poly(n)},
    mod:function(e){if(this.len()-e.len()<0)return this;var r=glog(this.get(0))-glog(e.get(0));var n=new Array(this.num.length);for(var i=this.shift;i<this.num.length;i++)n[i]=this.num[i];for(var i=0;i<e.len();i++)n[i]^=gexp(glog(e.get(i))+r);return new Poly(n,1).mod(e)}
  };

  function errPoly(n){var p=new Poly([1]);for(var i=0;i<n;i++)p=p.mul(new Poly([1,gexp(i)]));return p}

  // RS blocks for ECC Level M
  var RSB=[[],[1,26,19],[1,44,34],[1,70,55],[2,50,32],[2,67,43],[4,43,27],[4,49,31],[2,60,37,2,58,40],[3,58,36,2,54,42],[4,69,43,1,69,57]];

  function bch18(data){var d=data<<12;while(blen(d)>=13)d^=7973<<(blen(d)-13);return(data<<12)|d}
  function bch15(data){var d=data<<10;while(blen(d)>=11)d^=1335<<(blen(d)-11);return(data<<10)|d^21522}
  function blen(data){var l=0;while(data>0){l++;data>>>=1}return l}

  function makeQR(text,ver){
    ver=ver||3;
    var N=ver*4+17;
    var mod=[];var res=[];
    for(var i=0;i<N;i++){mod[i]=new Array(N);res[i]=new Array(N)}
    for(var i=0;i<N;i++)for(var j=0;j<N;j++){mod[i][j]=false;res[i][j]=false}

    function setFinder(r,c){
      for(var dr=-1;dr<=7;dr++)for(var dc=-1;dc<=7;dc++){
        var nr=r+dr,nc=c+dc;
        if(nr<0||nr>=N||nc<0||nc>=N)continue;
        res[nr][nc]=true;
        mod[nr][nc]=(dr>=0&&dr<=6&&(dc===0||dc===6))||(dc>=0&&dc<=6&&(dr===0||dr===6))||(dr>=2&&dr<=4&&dc>=2&&dc<=4);
      }
    }
    setFinder(0,0);setFinder(0,N-7);setFinder(N-7,0);

    // Timing
    for(var i=8;i<N-8;i++){if(!res[6][i]){res[6][i]=true;mod[6][i]=i%2===0}if(!res[i][6]){res[i][6]=true;mod[i][6]=i%2===0}}

    // Alignment
    var apos=PAT[ver];
    for(var pi=0;pi<apos.length;pi++)for(var pj=0;pj<apos.length;pj++){
      var ar=apos[pi],ac=apos[pj];
      if(res[ar][ac])continue;
      for(var dr=-2;dr<=2;dr++)for(var dc=-2;dc<=2;dc++){
        res[ar+dr][ac+dc]=true;
        mod[ar+dr][ac+dc]=dr===-2||dr===2||dc===-2||dc===2||(dr===0&&dc===0);
      }
    }

    // Dark module
    mod[N-8][8]=true;res[N-8][8]=true;

    // Format info placeholder (ECC M, mask 0)
    var fmt=bch15(1<<3|0);
    var fp1=[[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
    var fp2=[[N-1,8],[N-2,8],[N-3,8],[N-4,8],[N-5,8],[N-6,8],[N-7,8],[8,N-8],[8,N-7],[8,N-6],[8,N-5],[8,N-4],[8,N-3],[8,N-2],[8,N-1]];
    for(var i=0;i<15;i++){var v=((fmt>>i)&1)===1;mod[fp1[i][0]][fp1[i][1]]=v;res[fp1[i][0]][fp1[i][1]]=true;mod[fp2[i][0]][fp2[i][1]]=v;res[fp2[i][0]][fp2[i][1]]=true}

    // Encode data
    var bytes=[];
    for(var i=0;i<text.length;i++){var c=text.charCodeAt(i);if(c<128)bytes.push(c);else if(c<2048){bytes.push(192|(c>>6));bytes.push(128|(c&63))}else{bytes.push(224|(c>>12));bytes.push(128|((c>>6)&63));bytes.push(128|(c&63))}}

    var rsb=RSB[ver];
    var blocks=[];
    for(var i=0;i<rsb.length;i+=3)for(var j=0;j<rsb[i];j++)blocks.push({tc:rsb[i+1],dc:rsb[i+2]});
    var tdc=blocks.reduce(function(s,b){return s+b.dc},0);

    var buf=[];
    buf.push(0,1,0,0); // mode byte
    var L=bytes.length;
    for(var i=7;i>=0;i--)buf.push((L>>i)&1);
    for(var i=0;i<bytes.length;i++)for(var j=7;j>=0;j--)buf.push((bytes[i]>>j)&1);
    for(var i=0;i<4&&buf.length<tdc*8;i++)buf.push(0);
    while(buf.length%8)buf.push(0);
    while(buf.length<tdc*8){for(var j=7;j>=0;j--)buf.push((0xEC>>j)&1);if(buf.length<tdc*8)for(var j=7;j>=0;j--)buf.push((0x11>>j)&1)}
    var data=[];for(var i=0;i<buf.length;i+=8){var bv=0;for(var j=0;j<8;j++)bv=(bv<<1)|(buf[i+j]||0);data.push(bv)}

    var off=0,dcd=[],ecd=[];
    var mdc=0,mec=0;
    for(var r=0;r<blocks.length;r++){
      var dc=blocks[r].dc,ec=blocks[r].tc-dc;
      mdc=Math.max(mdc,dc);mec=Math.max(mec,ec);
      dcd[r]=data.slice(off,off+dc);off+=dc;
      var ep=errPoly(ec),rp=new Poly(dcd[r],ep.len()-1),mp=rp.mod(ep);
      ecd[r]=[];
      for(var i=0;i<ep.len()-1;i++){var mi=i+mp.len()-(ep.len()-1);ecd[r].push(mi>=0?mp.get(mi):0)}
    }
    var tc=blocks.reduce(function(s,b){return s+b.tc},0),res2=new Array(tc),idx=0;
    for(var i=0;i<mdc;i++)for(var r=0;r<blocks.length;r++)if(i<dcd[r].length)res2[idx++]=dcd[r][i];
    for(var i=0;i<mec;i++)for(var r=0;r<blocks.length;r++)if(i<ecd[r].length)res2[idx++]=ecd[r][i];

    // Place data with mask 0
    var bits=[];for(var i=0;i<res2.length;i++)for(var j=7;j>=0;j--)bits.push((res2[i]>>j)&1);
    var bi=0,up=true,col=N-1;
    while(col>0){
      if(col===6)col--;
      for(var rs=0;rs<N;rs++){
        var row=up?N-1-rs:rs;
        for(var dc=0;dc<2;dc++){
          var c=col-dc;
          if(res[row][c])continue;
          var bit=bits[bi++]||0;
          if((row+c)%2===0)bit^=1; // mask 0
          mod[row][c]=bit===1;
        }
      }
      up=!up;col-=2;
    }
    return{modules:mod,size:N};
  }

  function getVersion(text){
    var b=0;for(var i=0;i<text.length;i++){var c=text.charCodeAt(i);if(c<128)b++;else if(c<2048)b+=2;else b+=3}
    if(b<=19)return 1;if(b<=34)return 2;if(b<=55)return 3;if(b<=80)return 4;return 5;
  }

  function draw(canvasId,data,size){
    size=size||120;
    var canvas=document.getElementById(canvasId);
    if(!canvas)return;
    var ex=document.getElementById(canvasId+'_qr');
    if(ex)ex.remove();

    if(!data||data.indexOf('XXXX')>=0||/^KIN-[A-Z]+-[A-Z]+-0{3}-U\d+$/.test(data)){
      canvas.style.display='block';canvas.width=size;canvas.height=size;
      var ctx=canvas.getContext('2d');
      ctx.fillStyle='#f0f6f0';ctx.fillRect(0,0,size,size);
      ctx.strokeStyle='#c8d8c8';ctx.strokeRect(2,2,size-4,size-4);
      ctx.fillStyle='#6a8a6a';ctx.font='bold '+Math.floor(size/8)+'px monospace';
      ctx.textAlign='center';ctx.fillText('QR',size/2,size/2+4);
      return;
    }

    try{
      var ver=getVersion(data);
      var qr=makeQR(data,ver);
      var mc=qr.size;
      var cs=Math.max(2,Math.floor((size-8)/mc));
      var mg=Math.floor((size-mc*cs)/2);
      var ts=size;

      var tc=document.createElement('canvas');tc.width=ts;tc.height=ts;
      var ctx=tc.getContext('2d');
      ctx.fillStyle='#ffffff';ctx.fillRect(0,0,ts,ts);
      ctx.fillStyle='#0f4c35';
      for(var r=0;r<mc;r++)for(var c=0;c<mc;c++){
        if(qr.modules[r][c])ctx.fillRect(mg+c*cs,mg+r*cs,cs,cs);
      }

      var img=document.createElement('img');
      img.id=canvasId+'_qr';img.width=size;img.height=size;
      img.src=tc.toDataURL('image/png');
      img.style.cssText='display:block;border-radius:2px;';
      canvas.style.display='none';
      canvas.parentNode.insertBefore(img,canvas.nextSibling);
    }catch(err){
      console.error('QR error:',err,data);
      canvas.style.display='block';canvas.width=size;canvas.height=size;
      var ctx=canvas.getContext('2d');
      ctx.fillStyle='#fff';ctx.fillRect(0,0,size,size);
      ctx.strokeStyle='#0f4c35';ctx.lineWidth=2;ctx.strokeRect(2,2,size-4,size-4);
      ctx.fillStyle='#0f4c35';ctx.font=Math.floor(size/14)+'px monospace';
      ctx.textAlign='center';ctx.fillText(data.substring(0,15),size/2,size/2);
    }
  }

  function toDataURL(data,size){
    try{
      var ver=getVersion(data||'X');
      var qr=makeQR(data,ver);
      var mc=qr.size;size=size||200;
      var cs=Math.max(4,Math.floor((size-16)/mc));
      var mg=Math.floor((size-mc*cs)/2);
      var tc=document.createElement('canvas');tc.width=size;tc.height=size;
      var ctx=tc.getContext('2d');
      ctx.fillStyle='#fff';ctx.fillRect(0,0,size,size);
      ctx.fillStyle='#0f4c35';
      for(var r=0;r<mc;r++)for(var c=0;c<mc;c++)if(qr.modules[r][c])ctx.fillRect(mg+c*cs,mg+r*cs,cs,cs);
      return tc.toDataURL('image/png');
    }catch(e){return''}
  }

  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('canvas[data-qr]').forEach(function(c){
      var d=c.dataset.qr,s=parseInt(c.dataset.qrSize||'96');
      if(d)draw(c.id,d,s);
    });
  });

  return{draw:draw,toDataURL:toDataURL};
})();
