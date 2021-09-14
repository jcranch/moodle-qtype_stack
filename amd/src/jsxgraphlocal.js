define(["qtype_stack/jsxgraphcore-lazy"], function(JXG) {
 var JXL = {};

 JXL.finmap = {
  board : null,
  domain_list : [],
  domain_size : 0,
  domain_points : [],
  codomain_list : [],
  codomain_points : [],
  codomain_size : 0,
  value_list : [],
  index_value_list : [],
  image_list : [],
  index_image_list : [],
  fibres : [],
  index_fibres : [],
  inverse : [],
  is_injective : true,
  is_surjective : true,
  is_bijective : true,
  noninjective_witness : false,
  nonsurjective_witness : false,
  domain_label : null,
  codomain_label : null,
  map_label : null,
  arrows : [],
  display_opts : {
   domain_name : "A",
   domain_label_position : "auto",
   domain_x : 0,
   domain_y0 : 0,
   domain_dy : 1,
   domain_radius : 0.1,
   domain_color : "red",
   domain_label_offset : -4,
   codomain_name : "B",
   codomain_label_position : "auto",
   codomain_x : 1,
   codomain_y0 : 0,
   codomain_dy : 1,
   codomain_radius : 0.1,
   codomain_color : "blue",
   codomain_label_offset : 4,
   map_name : "f",
   map_label_position : "auto",
   arrow_shrink : 0.95,
   arrow_color : "grey",
   domain_ring_radius : 0.3,
   codomain_ring_radius : 0.3,
   noninjective_color : "orange",
   nonsurjective_color : "purple"
  } 
 };
 
 JXL.finmap.draw = function(board,display_opts = null) {
  this.board = board;
  
  var D = Object.assign({},this.display_opts);
  if (display_opts) {
   D = Object.assign(D,display_opts);
  }

  var nA = this.domain_size;
  var nB = this.codomain_size;

  this.domain_points = [];
  var s = D.domain_radius;
  var c = D.domain_color;
  var o = D.domain_label_offset;
    
  for (i = 1; i <= nA; i++) {
   var x = D.domain_x;
   var y = D.domain_y0 + (nA - i) * D.domain_dy;
   var p = board.create('point',[x,y],{size : s, name : this.domain_list[i-1],
				       fillColor : c, strokeColor : c,
				       label : { autoPosition : true, offset : [o,0] }});
   p.domain_index = i;
   p.ring = null;
   this.domain_points.push(p);
  }
    
  this.codomain_points = [];
  var s = D.codomain_radius;
  var c = D.codomain_color;
  var o = D.codomain_label_offset;
    
  for (i = 1; i <= nB; i++) {
   var x = D.codomain_x;
   var y = D.codomain_y0 + (nB - i) * D.codomain_dy;
   var q = board.create('point',[x,y],{size : s, name : this.codomain_list[i-1],
				       fillColor : c, strokeColor : c,
				       label : { autoPosition : true, offset : [o,0] }});
   q.codomain_index = i;
   q.ring = null;
   q.fibre = [];   
   this.codomain_points.push(q);
  }

  var t = D.arrow_shrink;
  var c = D.arrow_color;
  this.arrows = [];
    
  for (var i = 1; i <= nA; i++) {
   var j = this.index_value_list[i-1];
   var p = this.domain_points[i-1];
   var q = this.codomain_points[j-1];
   q.fibre.push(p);
   var r0 = [t * p.X() + (1 - t) * q.X(), t * p.Y() + (1 - t) * q.Y()];
   var r1 = [(1 - t) * p.X() + t * q.X(), (1 - t) * p.Y() + t * q.Y()];
   var a = board.create('arrow',[r0,r1],{strokeColor : c});
   p.arrow = a;
   this.arrows.push(a);
  }

  var dp = D.domain_label_position;
  if (dp && dp != 'none') {
   var x,y;
   if (dp == 'auto') {
    x = D.domain_x;
    y = D.domain_y0 - D.domain_dy;
   } else {
    x = parseFloat(dp[0]);
    y = parseFloat(dp[1]);
   }

   this.domain_label = board.create('text',[x,y,D.domain_name]);
  }
    
  var dp = D.codomain_label_position;
  if (dp && dp != 'none') {
   var x,y;
   if (dp == 'auto') {
    x = D.codomain_x;
    y = D.codomain_y0 - D.codomain_dy;
   } else {
    x = parseFloat(dp[0]);
    y = parseFloat(dp[1]);
   }

   this.codomain_label = board.create('text',[x,y,D.codomain_name]);
  }
    
  var dp = D.map_label_position;
  if (dp && dp != 'none') {
   var x,y;
   if (dp == 'auto') {
    x = 0.5 * (D.domain_x + D.codomain_x);
    y = 0.5 * (D.domain_y0 - D.domain_dy + D.codomain_y0 - D.codomain_dy);
   } else {
    x = parseFloat(dp[0]);
    y = parseFloat(dp[1]);
   }
   
   this.map_label = board.create('text',[x,y,D.map_name]);
  }
 };
 
 JXL.finmap.get_domain_point = function(a) {
  if (a instanceof JXG.Point) {
   return a;
  }

  for (var i = 1 ; i <= this.domain_size; i++) {
   if (a == this.domain_list[i-1]) {
    return this.domain_points[i-1];
   }
  }

  if (a == parseInt(a) && 1 <= a && a <= this.domain_size) {
   return this.domain_points[a-1];
  }

  return null;
 };
 
 JXL.finmap.get_codomain_point = function(b) {
  if (b instanceof JXG.Point) {
   return b;
  }

  for (var i = 1 ; i <= this.codomain_size; i++) {
   if (b == this.codomain_list[i-1]) {
    return this.codomain_points[i-1];
   }
  }

  if (b == parseInt(b) && 1 <= b && b <= this.codomain_size) {
   return this.codomain_points[b-1];
  }

  return null;
 };
 
 JXL.finmap.make_domain_ring = function(a,col = 'black') {
  var p = this.get_domain_point(a);
  if (! p) { return ; }
  var r = this.display_opts.domain_ring_radius;
  p.ring = this.board.create('circle',[[p.X(),p.Y()],r],{strokeColor : col})
 };
 
 JXL.finmap.make_codomain_ring = function(b,col = 'black') {
  var q = this.get_codomain_point(b);
  if (! q) { return ; }
  var r = this.display_opts.codomain_ring_radius;
  q.ring = this.board.create('circle',[[q.X(),q.Y()],r],{strokeColor : col})
 };

 JXL.finmap.show_fibre = function(b0,col = 'orange') {
  var b = this.get_codomain_point(b0);
  this.make_codomain_ring(b,col);
  for (j in b.fibre) {
   var a = b.fibre[j];
   this.make_domain_ring(a,col);
   a.arrow.setAttribute({strokeColor : col});
  }
 };

 JXL.finmap.show_noninjective = function() {
  if (this.noninjective_witness) {
   this.show_fibre(this.noninjective_witness[1],
		   this.display_opts.noninjective_color);
  }
 };
 
 JXL.finmap.show_nonsurjective = function() {
  if (this.nonsurjective_witness) {
   this.show_fibre(this.nonsurjective_witness[1],
		   this.display_opts.nonsurjective_color);
  }
 };
 
 JXL.venn2 = {
  board : null,
  display_opts : {
   cx : 0,
   cy : 0,
   dx : 0.7,
   r  : 1,
   A_color  : '#00FF88',
   B_color  : '#0088FF',
   AB_color : '#FFAA00'
  }
 }
   
 JXL.venn2.draw = function(board,display_opts = null) {
  this.board = board;
    
  var D = Object.assign({},this.display_opts);
  if (display_opts) {
   D = Object.assign(D,display_opts);
  }
  
  this.ac = board.create('point',[D.cx-D.dx,D.cy],{visible : false});
  this.bc = board.create('point',[D.cx+D.dx,D.cy],{visible : false});
  this.Ac = board.create('circle',[this.ac,D.r],{visible : false});
  this.Bc = board.create('circle',[this.bc,D.r],{visible : false});
  this.i0 = board.create('intersection',[this.Ac,this.Bc,0],{visible : false});
  this.i1 = board.create('intersection',[this.Ac,this.Bc,1],{visible : false});
  this.c0 = board.create('arc',[this.ac,this.i0,this.i1],{visible : false});
  this.c1 = board.create('arc',[this.bc,this.i1,this.i0],{visible : false});
  this.c2 = board.create('arc',[this.ac,this.i1,this.i0],{visible : false});
  this.c3 = board.create('arc',[this.bc,this.i0,this.i1],{visible : false});
  this.c2.reverse = true;
  this.c3.reverse = true;
  this.A  = JXG.joinCurves(board,[this.c1,this.c2],
                 {strokeColor : this.A_color, fillColor : this.A_color});
  this.B  = JXG.joinCurves(board,[this.c0,this.c3],
                 {strokeColor : this.B_color, fillColor : this.B_color});
  this.AB = JXG.joinCurves(board,[c0,c1],
                 {strokeColor : this.AB_color, fillColor : this.AB_color});
  
 }
  
 return JXL;
});
