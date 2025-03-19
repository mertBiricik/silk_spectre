from django.shortcuts import render, get_object_or_404
from django.db import transaction
from rest_framework import viewsets, status, generics
from rest_framework.decorators import action
from rest_framework.response import Response
from rest_framework.views import APIView

from .models import Poll, Choice
from .serializers import PollSerializer, ChoiceSerializer, PollResultsSerializer, VoteSerializer

class PollViewSet(viewsets.ModelViewSet):
    """ViewSet for polls."""
    queryset = Poll.objects.all()
    serializer_class = PollSerializer
    
    def get_queryset(self):
        """Filter polls by active status if requested."""
        queryset = Poll.objects.all()
        active = self.request.query_params.get('active')
        
        if active == 'true':
            queryset = queryset.filter(is_active=True)
        elif active == 'false':
            queryset = queryset.filter(is_active=False)
            
        return queryset.order_by('-created_at')
    
    @action(detail=True, methods=['get'])
    def results(self, request, pk=None):
        """Get the results of a specific poll."""
        poll = self.get_object()
        serializer = PollResultsSerializer(poll)
        return Response(serializer.data)
    
    @action(detail=True, methods=['post'])
    def vote(self, request, pk=None):
        """Vote on a poll choice."""
        poll = self.get_object()
        
        if not poll.is_open():
            return Response(
                {"detail": "This poll is not currently open for voting."},
                status=status.HTTP_400_BAD_REQUEST
            )
        
        serializer = VoteSerializer(data=request.data, context={'poll_id': poll.id})
        if serializer.is_valid():
            choice_id = serializer.validated_data['choice_id']
            
            with transaction.atomic():
                choice = get_object_or_404(Choice, id=choice_id, poll=poll)
                choice.votes += 1
                choice.save()
            
            return Response({"detail": "Vote recorded successfully."})
        
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)
    
    @action(detail=True, methods=['post'])
    def activate(self, request, pk=None):
        """Activate a poll."""
        poll = self.get_object()
        poll.is_active = True
        poll.save()
        return Response({"detail": "Poll activated successfully."})
    
    @action(detail=True, methods=['post'])
    def deactivate(self, request, pk=None):
        """Deactivate a poll."""
        poll = self.get_object()
        poll.is_active = False
        poll.save()
        return Response({"detail": "Poll deactivated successfully."})

class ActivePollView(generics.RetrieveAPIView):
    """View to get the currently active poll."""
    serializer_class = PollSerializer
    
    def get_object(self):
        """Get the most recently activated poll that is currently active."""
        return get_object_or_404(Poll, is_active=True)
