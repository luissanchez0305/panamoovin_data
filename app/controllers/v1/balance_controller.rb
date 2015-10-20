# The MIT License (MIT)
# 
# Copyright (c) 2015 Juan M. Merlos, panatrans.org
# 
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
# 
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

module V1
  class BalanceController < ApplicationController
  
    def show      
      card_number = params[:id]
      @data = nil
      url = "http://200.46.245.230:8080/PortalCAE-WAR-MODULE/SesionPortalServlet?accion=6&NumDistribuidor=99&NomUsuario=usuInternet&NomHost=AFT&NomDominio=aft.cl&Trx=&RutUsuario=0&NumTarjeta=" + card_number +  "&bloqueable="
      puts "Getting card data from: " + url
      doc = Nokogiri::HTML(open(url))
      # just uncomment to checkout what a piece of HTML crap is generated by MiBus
      #puts doc.inspect    
    
      # Among the HTML filth, we are looking for the <tr> with the class .verdanabold-ckc
      raw_data = doc.css('.verdanabold-ckc')
    
      #puts raw_data
      # if number of items is >5 the card number does not have a contract or they may have changed the HTML
      if raw_data.length < 5
        puts "raw_data: incorrect card number: " + card_number
      else 
        # on the raw_data array evens have the name, odds have the values
        @data = {
          card_number: raw_data[1].text,
          contract_status: raw_data[3].text,
          balance:  raw_data[5].text,
          last_usage: raw_data[7].text
        }
      end
    
      if ! @data.nil?
          render :json => {:status => "success", :data => @data}
      else 
          render_json_fail(:unprocessable_entity, ['¡Chuuleta! El número de tarjeta parece no existir. Por favor, revisa si es correcto.'])
      end       
    end # index
  end # class
end