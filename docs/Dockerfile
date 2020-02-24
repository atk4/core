FROM  python:2-stretch as builder

WORKDIR /www

ADD requirements.txt .

RUN pip install  pip==9.0.1 wheel==0.29.0 \
    && pip install -r requirements.txt

ADD . .

RUN make html
#RUN cp -R images build/html/images

FROM  nginx:latest

COPY --from=builder /www/build/html /usr/share/nginx/html